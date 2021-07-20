<?php

namespace Adapterap\NestedSet;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait NestedSetBuilder
 *
 * @package Adapterap\NestedSet
 * @method NestedSetModelTrait|Model getModel()
 */
trait NestedSetBuilderTrait
{
    /**
     * Подготавливает билдер
     *
     * @return Builder
     */
    public function prepareBuilder(): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        $scoped = $currentModel->getScopeAttributes();

        foreach ($scoped as $attribute) {
            $value = $currentModel->getAttributeValue($attribute);

            if ($value === null) {
                continue;
            }

            $this->where($attribute, $value);
        }

        return $this;
    }

    /**
     * Фильтр по корневым элементам.
     *
     * @return Builder|$this
     */
    public function whereDoesNotHaveParent(): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereDoesNotHaveParent($this->prepareBuilder());
    }

    /**
     * Фильтр по корневым элементам.
     *
     * @return Builder|$this
     */
    public function whereIsRoot(): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereIsRoot($this->prepareBuilder());
    }

    /**
     * Сортировка дерева по индексу вложенности.
     *
     * @param string $direction
     *
     * @return Builder|$this
     */
    public function orderByLft(string $direction = 'asc'): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeOrderByLft($this->prepareBuilder(), $direction);
    }

    /**
     * Фильтр по дочерним элементам указанной модели.
     *
     * @param Model $model
     *
     * @return Builder|$this
     */
    public function whereParent(Model $model): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereParent($this->prepareBuilder(), $model);
    }

    /**
     * Фильтр по дочерним элементам указанной модели.
     *
     * @param mixed $primary
     *
     * @return Builder|$this
     */
    public function whereParentId($primary): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereParentId($this->prepareBuilder(), $primary);
    }

    /**
     * Фильтр по потомкам указанного предка.
     *
     * @param Model $model
     *
     * @return Builder|$this
     */
    public function whereAncestor(Model $model): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereAncestor($this->prepareBuilder(), $model);
    }

    /**
     * Фильтр по потомкам указанного предка.
     *
     * @param mixed $primary
     *
     * @return Builder|$this
     */
    public function whereAncestorId($primary): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereAncestorId($this->prepareBuilder(), $primary);
    }

    /**
     * Фильтр по конечным узлам
     *
     * @return Builder
     */
    public function whereIsLeafNodes(): Builder
    {
        /** @var Builder $this */
        /** @var NestedSetModelTrait $currentModel */
        $currentModel = $this->getModel();

        return $currentModel->scopeWhereIsLeafNodes($this->prepareBuilder());
    }
}
