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
     * Свойство, меняющее поведение билдера при перемещении поддерева.
     *
     * @var bool
     */
    protected static bool $willRebase = false;

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
            $this->nestedSetDriver = new MySqlDriver(
                $this->getTable(),
                $this->getKeyName(),
                $this->lftName,
                $this->rgtName,
                $this->parentIdName,
                $this->depthName
            );
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
        // При обновлении элемента, следует обновлять все поддерево, а не только сам элемент
        if ($this->exists && static::$willRebase && $this->isDirty($this->parentIdName)) {
            /** @var Model|NestedSet $this */
            $nestedSetBuilder = new NestedSetBuilder($query->getQuery());
            $nestedSetBuilder->setModel($this);

            return $nestedSetBuilder;
        }

        return parent::setKeysForSaveQuery($query);
    }
}
