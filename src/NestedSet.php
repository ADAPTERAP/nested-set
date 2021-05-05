<?php

namespace Adapterap\NestedSet;

use Adapterap\NestedSet\Drivers\MySqlDriver;
use Adapterap\NestedSet\Drivers\NestedSetDriver;
use Adapterap\NestedSet\Exceptions\NestedSetDriverNotSupported;
use Adapterap\NestedSet\Traits\Attributes;
use Adapterap\NestedSet\Traits\Relations;
use Adapterap\NestedSet\Traits\Subscriber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait NestedSet
 *
 * @package Adapterap\NestedSet
 * @mixin Model
 */
trait NestedSet
{
    use Attributes, Subscriber, Relations;

    /**
     * Свойство, меняющее поведение builder при перемещении поддерева.
     *
     * @var bool
     */
    protected bool $nestedSetNeedToSubstituteBuilder = false;

    /**
     * Драйвер для работы с БД.
     *
     * @var NestedSetDriver
     */
    public NestedSetDriver $nestedSetDriver;

    /**
     * Инициализация трейта.
     */
    protected function initializeNestedSet(): void
    {
        $connectionDriverName = $this->getConnection()->getDriverName();

        if ($connectionDriverName === 'mysql') {
            /** @var Model|NestedSet $this */
            $this->nestedSetDriver = new MySqlDriver($this);
        } else {
            throw new NestedSetDriverNotSupported($connectionDriverName);
        }
    }

    /**
     * Set the keys for a save update query.
     *
     * @param Builder $query
     *
     * @return Builder
     */
    protected function setKeysForSaveQuery($query): Builder
    {
        // При обновлении элемента, следует обновлять все поддерево, а не только сам элемент.
        // При удалении элемента также следует удалять все поддерево.
        if ($this->nestedSetNeedToSubstituteBuilder) {
            /** @var Model|NestedSet $this */
            $nestedSetBuilder = new NestedSetBuilder($query->getQuery());
            $nestedSetBuilder->setModel($this);

            $this->nestedSetNeedToSubstituteBuilder = false;

            return $nestedSetBuilder;
        }

        return parent::setKeysForSaveQuery($query);
    }
}
