<?php

namespace Luminix\Backend\Controllers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Luminix\Backend\Facades\Finder;
use Luminix\Backend\Requests\IndexRequest;
use Luminix\Backend\Resources\DefaultCollection;
use Luminix\Backend\Resources\WithResource;

class ResourceController extends Controller
{

    use Macroable;

    protected function inferRequestParameters()
    {

        /** @var Request */
        $request = request();

        $name = $request->route()->getName();

        [, $name, $method] = explode('.', $name);

        $models = Finder::all();
        $class = $models[$name];

        if (!class_exists($class)) {
            abort(404);
        }

        // Relation routes are named "{relation}:{action}"; their permission is
        // configured under the action alone ('sync', 'attach', 'detach').
        $permissionKey = str_contains($method, ':') ? explode(':', $method)[1] : $method;

        $permission = config('luminix.backend.security.permissions.' . $permissionKey, null);

        return [
            'class' => $class,
            'alias' => $name,
            'permission' => $permission,
            'method' => $method
        ];
    }

    public function fillRelationships(Request $request, $item)
    {
        
        $data = $request->all();
        //$item->fill($data);

        foreach ($data as $key => $value)
        {

            $method = Str::camel($key);

            if (!method_exists($item, $method) && method_exists($item, $key)) {
                $method = $key;
            }
            
            $fillable = $item->getFillable();

            if (
                in_array($key, $fillable)
                || !method_exists($item, $method)
                || (!is_array($value) && !is_null($value))
            ) 
            {
                continue;
            }

            // check if is a "BelongsTo" relation
            // and if true, sets `{$key}_id` attribute
            $reflection = new \ReflectionMethod($item, $method);
            if ($reflection->hasReturnType() 
                && (
                    $reflection->getReturnType()->getName() == BelongsTo::class 
                    || is_subclass_of($reflection->getReturnType()->getName(), BelongsTo::class)
                )
            ) 
            {
                /** @var BelongsTo */
                $relation = $item->{$method}();
                $foreignKey = $relation->getForeignKeyName();

                if (is_null($value)) {
                    $item->{$foreignKey} = null;
                    continue;
                }

                $ownerKey = $relation->getOwnerKeyName();

                if (!isset($value[$ownerKey]) || !in_array($foreignKey, $fillable))
                {
                    continue;
                }
                $item->{$foreignKey} = $value[$ownerKey];
            }
        }
    }

    protected function getRequestedRelation(Request $request, $id)
    {
        
        [
            'class' => $class,
            'alias' => $alias,
            'permission' => $permission,
            'method' => $method
        ] = $this->inferRequestParameters();

        [$relationName, $action] = explode(':', $method);

        $item = $this->findItem($request, $id);

        $this->authorizeItem($permission, $alias, $item);


        if (!in_array($relationName, $item->getSyncs())) {
            abort(404);
        }

        $relation = $item->{$relationName}();

        if (!($relation instanceof BelongsToMany)) {
            abort(404);
        }

        if ($action === 'sync') {
            Validator::make($request->all(), [
                '*' => 'luminix_sync:' . $class . ',' . $relationName,
            ])->validate();
        }

        return $relation;
    }

    public function sync(Request $request, $id)
    {
        $relation = $this->getRequestedRelation($request, $id);

        $key = -1;

        $relation->sync(
            collect($request->all())->mapWithKeys(function ($relationItem) use (&$key, $relation) {
                if (is_int($relationItem) || is_string($relationItem)) {
                    $key++;
                    return [$key => $relationItem];
                }
                $key = $relationItem[$relation->getRelated()->getKeyName()];
                $pivot = Arr::except($relationItem, $relation->getRelated()->getKeyName());
                return [$relationItem[$relation->getRelated()->getKeyName()] => $pivot];
                
            })
        );

        return $this->respondWithItem($this->refetchItem($request, $id));
    }

    public function attach(Request $request, $id, $itemId)
    {
        $relation = $this->getRequestedRelation($request, $id);

        // Same reach check `luminix_sync` applies to each id given to sync.
        $related = $relation->getRelated();
        $permission = config('luminix.backend.security.permissions.index', 'read');

        $related->newQuery()
            ->where(function ($query) use ($related, $permission) {
                if ($permission && $related->hasNamedScope('allowed')) {
                    $query->allowed($permission);
                }
            })
            ->whereKey($itemId)
            ->firstOrFail();

        $relation->attach($itemId, $request->all());

        return $this->respondWithItem($this->refetchItem($request, $id));
    }

    public function detach(Request $request, $id, $itemId)
    {
        $relation = $this->getRequestedRelation($request, $id);

        $relation->detach($itemId);

        return $this->respondWithItem($this->refetchItem($request, $id));
    }

    protected function beforeSave(Request $request, $item)
    {
    }

    protected function afterSave(Request $request, $item)
    {
    }

    protected function beforeTransaction(Request $request, $item)
    {
    }

    protected function afterTransaction(Request $request, $item)
    {
    }

    protected function beforeCreate(Request $request, $item)
    {
    }

    protected function afterCreate(Request $request, $item)
    {
    }

    protected function beforeUpdate(Request $request, $item)
    {
    }

    protected function afterUpdate(Request $request, $item)
    {
    }

    protected function beforeDelete(Request $request, $item)
    {
    }

    protected function afterDelete(Request $request, $item)
    {
    }

    protected function beforeRestore(Request $request, $item)
    {
    }

    protected function afterRestore(Request $request, $item)
    {
    }

    protected function onTransactionError($error, Request $request, $item)
    {
        throw $error;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request 
     */
    public function index(IndexRequest $request)
    {
        [
            'class' => $class,
            'alias' => $alias,
            'permission' => $permission
        ] = $this->inferRequestParameters();

        $per_page = $request->per_page ?? 15;
        $minified = $request->minified ?? false;
        $instance = new $class;

        $columns = $minified ? [$instance->getKeyName(), $instance->getLabel()] : ['*'];

        /** @var Builder */
        $query = $class::luminixQuery($request, $permission);

        if ($minified) {
            $query->withOnly([]);
        }
        
        $data = $query->paginate($per_page, $columns);
    
        if ($permission && config('luminix.backend.security.gates_enabled', true)) {

            $items = collect($data->items());

            if ($minified) {

                // For minified queries, the models should be retrieved with all
                // attributes for proper permission checks.

                $items = $class::whereIn($instance->getKeyName(), $items->pluck($instance->getKeyName()))
                    ->get();
            }

            $items->each(function ($item) use ($permission, $alias) {
                if (!Gate::allows($permission . '-' . $alias, [$item])) {
                    abort(401, __('luminix-backend::backend.unauthorized'));
                }
            });
        }

        return response()->json(
            $this->respondWithCollection(
                $data
            )
        );
        
    }

    /**
     * Show the item.
     */
    public function show(Request $request, $id)
    {
        [
            'alias' => $alias,
            'permission' => $permission,
        ] = $this->inferRequestParameters();

        $item = $this->findItem($request, $id);

        $this->authorizeItem($permission, $alias, $item);

        return $this->respondWithItem($item);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request 
     */
    public function store(Request $request)
    {
        [
            'class' => $class,
            'alias' => $alias,
            'permission' => $permission
        ] = $this->inferRequestParameters();

        if ($permission 
                && config('luminix.backend.security.gates_enabled', true) 
                && !Gate::allows($permission . '-' . $alias, [null])
        ) {
            abort(401, __('luminix-backend::backend.unauthorized'));
        }

        $item = new $class;

        $item->validateRequest($request, 'store');

        $item->fill($request->all());

        $this->fillRelationships($request, $item);

        $this->beforeTransaction($request, $item);

        try {
            DB::transaction(function () use ($item, $request) {
                $item->fireLuminixEvent('saving');
                $item->fireLuminixEvent('creating');
    
                $this->beforeSave($request, $item);
                $this->beforeCreate($request, $item);
    
                $item->save();
    
                $this->afterSave($request, $item);
                $this->afterCreate($request, $item);
    
                $item->fireLuminixEvent('saved');
                $item->fireLuminixEvent('created');
            });
        } catch (\Throwable $th) {
            $this->onTransactionError($th, $request, $item);
        }

        $this->afterTransaction($request, $item);

        return $this->respondWithItem(
            $this->refetchItem($request, $item->getKey()),
            201
        );
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request 
     */
    public function update(Request $request, $id)
    {
        [
            'alias' => $alias,
            'permission' => $permission,
            'class' => $class,
        ] = $this->inferRequestParameters();

        $item = $this->findItem($request, $id);

        $this->authorizeItem($permission, $alias, $item);

        $item->validateRequest($request, 'update');
        
        $item->fill($request->all());

        $this->fillRelationships($request, $item);

        $this->beforeTransaction($request, $item);
        
        try {
            DB::transaction(function () use ($item, $request) {
                if ($request->query('restore')) {
                    $item->fireLuminixEvent('restoring');
                    $this->beforeRestore($request, $item);
    
                    $item->restore();
    
                    $this->afterRestore($request, $item);
                    $item->fireLuminixEvent('restored');
                }
    
                $item->fireLuminixEvent('saving');
                $item->fireLuminixEvent('updating');
    
                $this->beforeSave($request, $item);
                $this->beforeUpdate($request, $item);
    
                $item->save();
                
                $this->afterSave($request, $item);
                $this->afterUpdate($request, $item);
    
                $item->fireLuminixEvent('saved');
                $item->fireLuminixEvent('updated');
                
            });
        } catch (\Throwable $th) {
            $this->onTransactionError($th, $request, $item);
        }

        $this->afterTransaction($request, $item);

        return $this->respondWithItem(
            $this->refetchItem($request, $id)
        );
    }

    /**
     * Remove the specified resource from storage.
     * @param Request $request
     */
    public function destroy(Request $request, $id)
    {
        [
            'alias' => $alias,
            'permission' => $permission,
            'class' => $class,
        ] = $this->inferRequestParameters();

        $item = $this->findItem($request, $id);

        $this->authorizeItem($permission, $alias, $item);

        $this->beforeTransaction($request, $item);

        try {
            DB::transaction(function () use ($item, $request) {
                $item->fireLuminixEvent('deleting');
    
                $this->beforeDelete($request, $item);
    
                if ($request->force) {
                    $item->forceDelete();
                } else {
                    $item->delete();
                }
    
                $this->afterDelete($request, $item);
    
                $item->fireLuminixEvent('deleted');
            });
        } catch (\Throwable $th) {
            $this->onTransactionError($th, $request, $item);
        }

        $this->afterTransaction($request, $item);

        return response()->json(null, 204);
    }

    /**
     * Remove the specified resources from storage.
     * @param Request $request 
     */
    public function destroyMany(Request $request)
    {
        [
            'class' => $class,
            'alias' => $alias,
            'permission' => $permission
        ] = $this->inferRequestParameters();

        $instance = new $class;

        $request->validate($this->idsRules($instance));
        
        $ids = $request->ids;

        $query = $class::beforeLuminix($request)
            ->where(function ($query) use ($permission) {
                if ($permission) {
                    $query->allowed($permission);
                }
            })
            ->whereIn($instance->getKeyName(), $ids)
            ->afterLuminix($request);

        if ($request->force) {
            $query->withTrashed();
        }
        $items = $this->authorizeItems($permission, $alias, (clone $query)->get());

        if ($items->count() === 0) {
            abort(404);
        }

        $query->whereIn($instance->getKeyName(), $items->modelKeys());

        $this->beforeTransaction($request, $items);

        try {
            DB::transaction(function () use ($query, $request) {
                if ($request->force) {
                    $query->forceDelete();
                } else {
                    $query->delete();
                }
            });
        } catch (\Throwable $th) {
            $this->onTransactionError($th, $request, $items);
        }

        $this->afterTransaction($request, $items);

        return response()->json(null, 204);

    }

    /**
     * Restore the specified resources from storage.
     * @param Request $request 
     */
    public function restoreMany(Request $request)
    {
        [
            'class' => $class,
            'alias' => $alias,
            'permission' => $permission
        ] = $this->inferRequestParameters();

        $instance = new $class;

        $request->validate($this->idsRules($instance));
        
        $ids = $request->ids;

        $query = $class::beforeLuminix($request)
            ->where(function ($query) use ($permission) {
                if ($permission) {
                    $query->allowed($permission);
                }
            })
            ->onlyTrashed()
            ->whereIn($instance->getKeyName(), $ids)
            ->afterLuminix($request);

        $items = $this->authorizeItems($permission, $alias, (clone $query)->get());

        if ($items->count() === 0) {
            abort(404);
        }

        $query->whereIn($instance->getKeyName(), $items->modelKeys());

        $this->beforeTransaction($request, $items);

        try {
            DB::transaction(function () use ($query, $request) {
                $query->restore();
            });
        } catch (\Throwable $th) {
            $this->onTransactionError($th, $request, $items);
        }

        $this->afterTransaction($request, $items);

        return response()->json(null, 204);
    }

    public function respondWithItem($item, $status = 200)
    {
        ['class' => $class] = $this->inferRequestParameters();
        
        /** @var Request */
        $request = request();
        
        if ($request->wantsJson()) {

            $reflection = new \ReflectionClass($class);
            $resourceAttribute = $reflection->getAttributes(WithResource::class)[0] ?? null;

            if ($resourceAttribute) {
                $resource = $resourceAttribute->newInstance();
                
                if ($resource->single) {
                    return new $resource->single($item);
                }
            }

            return response()->json($item, $status);
        }

        if ($request->query('redirectTo')) {
            return redirect($request->query('redirectTo'));
        }

        return back()->with(['item' => $item]);
    }

    public function respondWithCollection($items, $status = 200)
    {
        ['class' => $class] = $this->inferRequestParameters();

        $reflection = new \ReflectionClass($class);
        $resourceAttribute = $reflection->getAttributes(WithResource::class)[0] ?? null;

        if ($resourceAttribute) {
            $resource = $resourceAttribute->newInstance();
            
            if ($resource->collection) {
                return new $resource->collection($items);
            }

            if ($resource->single) {
                return $resource->single::collection($items);
            }
        }
        
        return new DefaultCollection($items);
    }

    /**
     * Abort unless the Gate lets the caller perform `$permission` on `$item`.
     *
     * A caller who can't read the item gets the same 404 a missing id gets, so
     * a denied id can't be told apart from an absent one. A caller who can read
     * it but not perform the action gets 401.
     */
    protected function authorizeItem(?string $permission, string $alias, $item): void
    {
        if ($this->allowsItem($permission, $alias, $item)) {
            return;
        }

        if ($this->canReadItem($permission, $alias, $item)) {
            abort(401, __('luminix-backend::backend.unauthorized'));
        }

        throw (new ModelNotFoundException)->setModel(get_class($item));
    }

    /**
     * Keep the items the caller may act on. Items it can't read are dropped,
     * the same as ids that don't exist; a readable item the Gate denies aborts
     * the request with 401.
     */
    protected function authorizeItems(?string $permission, string $alias, Collection $items): Collection
    {
        return $items->filter(function ($item) use ($permission, $alias) {
            if ($this->allowsItem($permission, $alias, $item)) {
                return true;
            }

            if ($this->canReadItem($permission, $alias, $item)) {
                abort(401, __('luminix-backend::backend.unauthorized'));
            }

            return false;
        });
    }

    protected function allowsItem(?string $permission, string $alias, $item): bool
    {
        return !$permission
            || !config('luminix.backend.security.gates_enabled', true)
            || Gate::allows($permission . '-' . $alias, [$item]);
    }

    protected function canReadItem(string $permission, string $alias, $item): bool
    {
        $read = config('luminix.backend.security.permissions.show');

        return $permission !== $read && $this->allowsItem($read, $alias, $item);
    }

    /**
     * Rules for the `ids` of destroyMany/restoreMany. Existence is left to the
     * scoped query: an unscoped `exists` rule would answer 422 for a missing id
     * and 404 for a hidden one.
     */
    protected function idsRules($instance): array
    {
        return [
            'ids' => 'required|array',
            'ids.*' => $instance->getKeyType() === 'int' ? 'integer' : 'string',
        ];
    }

    public function findItem(Request $request, $id)
    {
        ['permission' => $permission] = $this->inferRequestParameters();

        return $this->itemQuery($request, $id, $permission)->firstOrFail();
    }

    /**
     * Re-fetch an item to build the response after a committed write.
     *
     * Applies `beforeLuminix`/`afterLuminix` but not `scopeAllowed`: the write
     * was already authorized and committed, so the response must never be a
     * 404, even when `scopeAllowed` hides the row from its own author.
     */
    public function refetchItem(Request $request, $id)
    {
        return $this->itemQuery($request, $id, null)->firstOrFail();
    }

    protected function itemQuery(Request $request, $id, ?string $permission): Builder
    {
        [
            'class' => $class,
            'method' => $method
        ] = $this->inferRequestParameters();

        $item = new $class;

        $query = $class::beforeLuminix($request)
            ->where(function ($query) use ($permission) {
                if ($permission) {
                    $query->allowed($permission);
                }
            })
            ->where($item->getKeyName(), $id)
            ->afterLuminix($request);

        if (($method === 'destroy' && $request->query('force'))
            || ($method === 'update' && $request->query('restore'))) {
            $query = $query->withTrashed();
        }

        return $query;
    }
}