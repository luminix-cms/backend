<?php

namespace Luminix\Backend\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Luminix\Backend\Facades\Finder;

trait HasRelationHandler
{
    public function getRelationships(): ?array
    {
        $class = new \ReflectionClass($this);

        $allMethods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methods = array_filter(
            $allMethods,
            function (\ReflectionMethod $method) {
                return // $method->getFileName() === $class->getFileName() && // only methods declared in the model
                    !$method->getParameters() // relationships have no parameters
                    && $method->hasReturnType() // check if the method has a return type
                    && is_subclass_of($method->getReturnType()->getName(), Relation::class); // check if the return type is a subclass of Relation
            }
        );

        $relations = [];
        foreach ($methods as $method) {
            $methodName = $method->getName();
            $returnType = $method->getReturnType()->getName();

            $type = (new \ReflectionClass($returnType))->getShortName();

            /** @var Relation */
            $relation = $this->{$methodName}();
            $relatedModel = $relation->getRelated();

            $model = get_class($relatedModel);

            if (!Finder::isLuminixModel($model)) {
                continue;
            }

            if (method_exists($relation, 'getForeignKeyName')) {
                $foreignKey = $relation->getForeignKeyName();
            } else {
                $foreignKey = null;
            }

            if (method_exists($relation, 'getOwnerKeyName')) {
                $ownerKey = $relation->getOwnerKeyName();
            } else {
                $ownerKey = null;
            }

            $relations[Str::snake($methodName)] = [
                'type' => $type,
                'model' => $model::getAlias(),
                'foreignKey' => $foreignKey,
                'ownerKey' => $ownerKey,
            ];
        }

        return empty($relations)
            ? null
            : $relations;
    }

    public function getSyncs(): array
    {
        if (!isset($this->syncs)) {
            return [];
        }

        return $this->syncs;
    }
}