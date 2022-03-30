<?php

namespace Adapterap\NestedSet\Relations;

use Adapterap\NestedSet\Exceptions\NestedSetModelHasNoScope;
use Adapterap\NestedSet\NestedSetModelTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;

/**
 * Class BaseRelation.
 *
 * @property-read Model|NestedSetModelTrait $parent
 */
abstract class BaseRelation extends Relation
{
    /**
     * Направление сортировки по умолчанию.
     *
     * @var string
     */
    protected string $defaultOrderDirection = 'asc';

    /**
     * Set the base constraints on the relation query.
     */
    public function addConstraints(): void
    {
        if (!static::$constraints) {
            return;
        }

        static::addFiltersForModel($this->query, $this->parent);

        if (empty($this->query->getQuery()->orders)) {
            $this->query->orderBy($this->parent->getLftName(), $this->defaultOrderDirection);
        }
    }

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param Model[]|NestedSetModelTrait[] $models
     */
    public function addEagerConstraints(array $models): void
    {
        $this->query->where(static function (Builder $builder) use ($models) {
            foreach ($models as $model) {
                $builder->orWhere(static function (Builder $builder) use ($model) {
                    static::addFiltersForModel($builder, $model);
                });
            }
        });

        if (empty($this->query->getQuery()->orders)) {
            $this->query->orderBy($models[0]->getLftName(), $this->defaultOrderDirection);
        }
    }

    /**
     * Initialize the relation on a set of models.
     *
     * @param Model[] $models
     * @param string  $relation
     *
     * @return array
     */
    public function initRelation(array $models, $relation): array
    {
        foreach ($models as $index => $model) {
            $models[$index] = $model->setRelation($relation, $model->newCollection());
        }

        return $models;
    }

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param Model[]|NestedSetModelTrait[] $models
     * @param Collection                    $results
     * @param string                        $relation
     *
     * @return array
     */
    abstract public function match(array $models, Collection $results, $relation): array;

    /**
     * Get the results of the relationship.
     *
     * @return Collection|Model[]|NestedSetModelTrait[]
     */
    public function getResults(): Collection
    {
        if (empty($this->query->getQuery()->orders)) {
            $this->query->orderBy($this->parent->getLftName(), $this->defaultOrderDirection);
        }

        return $this->query->get();
    }

    /**
     * Get the key for comparing against the parent key in "has" query.
     *
     * @return string
     */
    public function getExistenceCompareKey(): string
    {
        return $this->getModel()->getKeyName();
    }

    /**
     * Добавляет фильтры к указанному билдеру для указанной модели.
     *
     * @param Builder                   $builder
     * @param Model|NestedSetModelTrait $model
     *
     * @return Builder
     */
    abstract protected static function addFiltersForModel(Builder $builder, Model $model): Builder;

    /**
     * Добавляет в фильтр билдера scopes.
     *
     * @param Builder $builder
     * @param Model   $model
     *
     * @return Builder
     */
    protected static function addScopeFilter(Builder $builder, Model $model): Builder
    {
        $scopes = $model->getScopeAttributes();
        $attributes = $model->getAttributes();

        foreach ($scopes as $scope) {
            if (!Arr::exists($attributes, $scope)) {
                throw new NestedSetModelHasNoScope($model, $scope);
            }

            $scopeValue = $model->getAttribute($scope);

            $builder->where($scope, $scopeValue);
        }

        return $builder;
    }
}
