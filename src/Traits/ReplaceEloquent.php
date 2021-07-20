<?php

namespace Adapterap\NestedSet\Traits;

use Illuminate\Database\Eloquent\Builder;
use Adapterap\NestedSet\Builders\NestedSetBuilder;
use Adapterap\NestedSet\Builders\NestedSetInternalBuilder;
use Adapterap\NestedSet\Collections\NestedSetCollection;
use Adapterap\NestedSet\NestedSetModelTrait;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Trait ReplaceEloquent
 * @package Adapterap\NestedSet\Traits
 * @method static NestedSetBuilder query()
 */
trait ReplaceEloquent
{
    /**
     * Свойство, меняющее поведение builder при перемещении поддерева.
     *
     * @var bool
     */
    protected bool $nestedSetNeedToSubstituteBuilder = false;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param QueryBuilder $query
     *
     * @return NestedSetBuilder()
     */
    public function newEloquentBuilder($query): EloquentBuilder
    {
        return new NestedSetBuilder($query);
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param array $models
     *
     * @return NestedSetCollection
     */
    public function newCollection(array $models = []): EloquentCollection
    {
        return new NestedSetCollection($models);
    }

    /**
     * @param array $attributes
     *
     * @return NestedSetBuilder|Builder
     */
    public static function scoped(array $attributes): Builder
    {
        $instance = new static();

        $instance->setRawAttributes($attributes);

        return $instance->newScopedQuery();
    }

    /**
     * Set the keys for a save update query.
     *
     * @param EloquentBuilder $query
     *
     * @return EloquentBuilder
     */
    protected function setKeysForSaveQuery($query): EloquentBuilder
    {
        // При обновлении элемента, следует обновлять все поддерево, а не только сам элемент.
        // При удалении элемента также следует удалять все поддерево.
        if ($this->nestedSetNeedToSubstituteBuilder) {
            /** @var Model|NestedSetModelTrait $this */
            $nestedSetBuilder = new NestedSetInternalBuilder($query->getQuery());
            $nestedSetBuilder->setModel($this);

            $this->nestedSetNeedToSubstituteBuilder = false;

            return $nestedSetBuilder;
        }

        return parent::setKeysForSaveQuery($query);
    }
}
