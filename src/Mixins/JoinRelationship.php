<?php

namespace KirschbaumDevelopment\EloquentJoins\Mixins;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;

class JoinRelationship
{
    public function joinRelationship()
    {
        return function ($relation, $callback = null, $joinType = 'join') {
            if (Str::contains($relation, '.')) {
                return $this->joinNestedRelationship($relation, $callback, $joinType);
            }

            $relation = $this->getModel()->{$relation}();

            $this->{$joinType}($relation->getModel()->getTable(), function ($join) use ($relation, $callback) {
                $join->on(
                    sprintf('%s.%s', $relation->getModel()->getTable(), $relation->getForeignKeyName()),
                    '=',
                    $this->getModel()->getQualifiedKeyName()
                );

                if ($relation instanceof MorphOneOrMany) {
                    $join->where($relation->getMorphType(), '=', get_class($this->getModel()));
                }

                if ($callback && is_callable($callback)) {
                    $callback($join);
                }
            });

            return $this;
        };
    }

    public function leftJoinRelationship()
    {
        return function ($relation, $callback = null) {
            return $this->joinRelationship($relation, $callback, 'leftJoin');
        };
    }

    public function rightJoinRelationship()
    {
        return function ($relation, $callback = null) {
            return $this->joinRelationship($relation, $callback, 'rightJoin');
        };
    }

    public function joinNestedRelationship()
    {
        return function ($relations, $callback = null, $joinType = 'join') {
            $relations = explode('.', $relations);
            $latestRelation = null;

            foreach ($relations as $index => $relationName) {
                if (! $latestRelation) {
                    $currentModel = $this->getModel();
                    $relation = $currentModel->{$relationName}();
                    $relationModel = $relation->getModel();
                } else {
                    $currentModel = $latestRelation->getModel();
                    $relation = $currentModel->{$relationName}();
                    $relationModel = $relation->getModel();
                }

                $this->{$joinType}($relationModel->getTable(), function ($join) use ($relation, $relationName, $relationModel, $currentModel, $callback) {
                    $join->on(
                        sprintf('%s.%s', $relationModel->getTable(), $relation->getForeignKeyName()),
                        '=',
                        $currentModel->getQualifiedKeyName()
                    );

                    if ($relation instanceof MorphOneOrMany) {
                        $join->where($relation->getMorphType(), '=', get_class($currentModel));
                    }

                    if ($callback && is_array($callback) && isset($callback[$relationName])) {
                        $callback[$relationName]($join);
                    }
                });

                $latestRelation = $relation;
            }

            return $this;
        };
    }
}
