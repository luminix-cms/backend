<?php

namespace Luminix\Backend\Controllers;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Luminix\Backend\Requests\IndexRequest;
use Luminix\Backend\Resources\DefaultCollection;

class ResourceController extends Controller
{

    use Macroable;

    private function inferRequestParameters()
    {
        $name = request()->route()->getName();

        [, $name, $method] = explode('.', $name);

        $class = 'App\\Models\\' . Str::studly($name);

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
        $item->fill($data);

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
    
    public function syncRelationships(Request $request, $item)
    {
        foreach ($item->getSyncs() as $relation) {
            if ($request->has($relation) && method_exists($item, $relation)) {
                $reflection = new \ReflectionMethod($item, $relation);
                if (!$reflection->hasReturnType() 
                    || ($reflection->getReturnType()->getName() !== BelongsToMany::class 
                        && !is_subclass_of($reflection->getReturnType()->getName(), BelongsToMany::class)
                )) {
                    continue;
                }
                $key = -1;
                /** @var BelongsToMany */
                $relation = $item->{$relation}();

                $related = $relation->getRelated();
                $ownerKey = $related->getKeyName();

                $relation->sync(
                    collect($request->{$relation})->mapWithKeys(function ($relationItem) use (&$key, $ownerKey) {
                        if (!isset($relationItem['pivot'])) {
                            $key++;
                            return [$key => $relationItem[$ownerKey]];
                        }
                        $key = $relationItem[$ownerKey];
                        return [$relationItem[$ownerKey] => $relationItem['pivot']];
                    })
                );
            }
        }
    }

    public function beforeSave(Request $request, $item)
    {
    }

    public function afterSave(Request $request, $item)
    {
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

        if ($permission && config('luminix.backend.security.gates_enabled', true) && !Gate::allows($permission . '-' . $alias)) {
            abort(403);
        }

        
        $per_page = $request->per_page ?? 15;
        $minified = $request->minified ?? false;
        
        if ($minified) {
            $page = $request->page ?? 1;
            $model = new $class;
            $model->appends = [];
            return response()->json(
                $model->query()
                    ->luminixQuery($request, $permission)
                    ->withOnly([])
                    ->limit($per_page)
                    ->offset(($page - 1) * $per_page)
                    ->get([$model->getKeyName(), $model->getLabel()])
            );
        }
        
        return $this->respondWithCollection(
            $class::luminixQuery($request, $permission)
                ->paginate($per_page)
        );
        
    }

    /**
     * Show the item.
     */
    public function show(Request $request, $id)
    {
        [
            'alias' => $alias,
            'permission' => $permission
        ] = $this->inferRequestParameters();

        $item = $this->findItem($request, $id);

        if ($permission && config('luminix.backend.security.gates_enabled', true) && !Gate::allows($permission . '-' . $alias, $item)) {
            abort(403);
        }

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
                && !Gate::allows($permission . '-' . $alias)
        ) {
            abort(403);
        }

        $item = new $class;

        $item->validateRequest($request, 'store');

        $item->fill($request->all());

        $this->fillRelationships($request, $item);

        DB::transaction(function () use ($item, $request) {
            $this->beforeSave($request, $item);

            $item->save();
            $this->syncRelationships($request, $item);
            $this->afterSave($request, $item);
        });

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
            'permission' => $permission
        ] = $this->inferRequestParameters();

        $item = $this->findItem($request, $id);

        $item->validateRequest($request, 'update');

        if ($permission && config('luminix.backend.security.gates_enabled', true) && !Gate::allows($permission . '-' . $alias, $item)) {
            abort(403);
        }

        
        $item->fill($request->all());
        
        DB::transaction(function () use ($item, $request) {
            $this->beforeSave($request, $item);

            if ($request->query('restore')) {
                $item->restore();
            }
            $item->save();
            $this->syncRelationships($request, $item);
            $this->afterSave($request, $item);
        });

        return response()->json(
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
            'permission' => $permission
        ] = $this->inferRequestParameters();

        $item = $this->findItem($request, $id);

        if ($permission && config('luminix.backend.security.gates_enabled', true) && !Gate::allows($permission . '-' . $alias, $item)) {
            abort(403);
        }

        DB::transaction(function () use ($item, $request) {
            if ($request->force) {
                $item->forceDelete();
            } else {
                $item->delete();
            }
        });

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

        if ($permission && config('luminix.backend.security.gates_enabled', true) && !Gate::allows($permission . '-' . $alias)) {
            abort(403);
        }

        $instance = new $class;

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:' . $instance->getTable() . ',' . $instance->getKeyName(),
        ]);
        
        $ids = $request->ids;

        $items = $class::beforeLuminix($request)
            ->where(function ($query) use ($permission) {
                $query->allowed($permission);
            })
            ->whereIn($instance->getKeyName(), $ids)
            ->afterLuminix($request);

        if ($request->force) {
            $items = $items->withTrashed()->get();
        } else {
            $items = $items->get();
        }

        if ($items->count() === 0) {
            abort(404);
        }

        DB::transaction(function () use ($items, $request) {
            if ($request->force) {
                $items->each->forceDelete();
            } else {
                $items->each->delete();
            }
        });

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

        if ($permission && config('luminix.backend.security.gates_enabled', true) && !Gate::allows($permission . '-' . $alias)) {
            abort(403);
        }

        $instance = new $class;

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:' . $instance->getTable() . ',' . $instance->getKeyName(),
        ]);
        
        $ids = $request->ids;

        $items = $class::beforeLuminix($request)
            ->where(function ($query) use ($permission) {
                $query->allowed($permission);
            })
            ->withTrashed()
            ->whereIn($instance->getKeyName(), $ids)
            ->afterLuminix($request)
            ->get();

        if ($items->count() === 0) {
            abort(404);
        }

        DB::transaction(function () use ($items, $request) {
            $items->each->restore();
        });

        return $this->respondWithCollection($items);
    }

    /**
     * Import resources from spreadsheet.
     * @param Request $request 
     */
    public function import(Request $request)
    {
        abort(500, 'Not implemented');
    }

    /**
     * Export resources to spreadsheet.
     * @param Request $request 
     */
    public function export(Request $request)
    {
        abort(500, 'Not implemented');
    }

    public function respondWithItem($item, $status = 200)
    {
        ['class' => $class] = $this->inferRequestParameters();
        $class = class_basename($class);

        $namespace = App::getNamespace();

        if (class_exists($namespace . 'Http\Resources\\' . $class . 'Resource')) {
            $resource = $namespace . 'Http\Resources\\' . $class . 'Resource';
            return response()->json(new $resource($item), $status);
        }

        return response()->json($item, $status);
    }

    public function respondWithCollection($items, $status = 200)
    {
        ['class' => $class] = $this->inferRequestParameters();
        $class = class_basename($class);

        $namespace = App::getNamespace();

        if (class_exists($namespace . 'Http\Resources\\' . $class . 'Collection')) {
            $resource = $namespace . 'Http\Resources\\' . $class . 'Collection';
            return new $resource($items);
        }

        if (class_exists($namespace . 'Http\Resources\\' . $class . 'Resource')) {
            $resource = $namespace . 'Http\Resources\\' . $class . 'Resource';
            return $resource::collection($items);
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
                $query->allowed($permission);
            })
            ->where($item->getKeyName(), $id)
            ->afterLuminix($request);
        
        if ($method === 'update' && $request->query('restore')) {
            $query = $query->onlyTrashed();
        }

        if ($method === 'destroy' && $request->query('force')) {
            $query = $query->withTrashed();
        }

        $item = $query->firstOrFail();

        return $item;
    }
}