<?php

namespace Luminix\Backend\Controllers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
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

        $permission = config('luminix.backend.security.permissions.' . $method, null);

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

        $item = $class::findOrFail($id);

        if ($permission && config('luminix.backend.security.gates_enabled', true) && !Gate::allows($permission . '-' . $alias, [$item])) {
            abort(401, __('luminix-backend::backend.unauthorized'));
        }


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

        return $this->respondWithItem($this->findItem($request, $id));
    }

    public function attach(Request $request, $id, $itemId)
    {
        $relation = $this->getRequestedRelation($request, $id);

        $relation->getRelated()->findOrFail($itemId);

        $relation->attach($itemId, $request->all());

        return $this->respondWithItem($this->findItem($request, $id));
    }

    public function detach(Request $request, $id, $itemId)
    {
        $relation = $this->getRequestedRelation($request, $id);

        $relation->detach($itemId);

        return $this->respondWithItem($this->findItem($request, $id));
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
            'class' => $class,
        ] = $this->inferRequestParameters();

        $supposedItem = $class::findOrFail($id);

        if ($permission && config('luminix.backend.security.gates_enabled', true) && !Gate::allows($permission . '-' . $alias, [$supposedItem])) {
            abort(401, __('luminix-backend::backend.unauthorized'));
        }

        $item = $this->findItem($request, $id);

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
            $this->findItem($request, $item->getKey()),
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

        if ($permission && config('luminix.backend.security.gates_enabled', true) && !Gate::allows($permission . '-' . $alias, [$item])) {
            abort(401, __('luminix-backend::backend.unauthorized'));
        }

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
            $this->findItem($request, $id)
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

        if ($permission && config('luminix.backend.security.gates_enabled', true) && !Gate::allows($permission . '-' . $alias, [$item])) {
            abort(401, __('luminix-backend::backend.unauthorized'));
        }

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

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:' . $instance->getTable() . ',' . $instance->getKeyName(),
        ]);
        
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
        $items = (clone $query)->get();

        if ($items->count() === 0) {
            abort(404);
        }

        if ($permission && config('luminix.backend.security.gates_enabled', true)) {
            $items->each(function ($item) use ($permission, $alias) {
                if (!Gate::allows($permission . '-' . $alias, [$item])) {
                    abort(401, __('luminix-backend::backend.unauthorized'));
                }
            });
        }

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

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:' . $instance->getTable() . ',' . $instance->getKeyName(),
        ]);
        
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

        $items = (clone $query)->get();

        if ($items->count() === 0) {
            abort(404);
        }

        if ($permission && config('luminix.backend.security.gates_enabled', true)) {
            $items->each(function ($item) use ($permission, $alias) {
                if (!Gate::allows($permission . '-' . $alias, [$item])) {
                    abort(401, __('luminix-backend::backend.unauthorized'));
                }
            });
        }

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

    public function findItem(Request $request, $id)
    {
        [
            'class' => $class,
            'permission' => $permission,
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

        $item = $query->firstOrFail();

        return $item;
    }
}