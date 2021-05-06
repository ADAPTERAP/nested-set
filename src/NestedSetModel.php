<?php

namespace Adapterap\NestedSet;

use Adapterap\NestedSet\Builders\NestedSetBuilder;
use Adapterap\NestedSet\Drivers\MySqlDriver;
use Adapterap\NestedSet\Drivers\NestedSetDriver;
use Adapterap\NestedSet\Exceptions\NestedSetDriverNotSupported;
use Adapterap\NestedSet\Traits\Attributes;
use Adapterap\NestedSet\Traits\Relations;
use Adapterap\NestedSet\Traits\Scopes;
use Adapterap\NestedSet\Traits\Subscriber;
use Adapterap\NestedSet\Traits\Tree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Trait NestedSet
 *
 * @package Adapterap\NestedSet
 * @mixin Model
 */
trait NestedSetModel
{
    use Attributes, Subscriber, Relations, Tree, Scopes;

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
    protected function initializeNestedSetModel(): void
    {
        $connectionDriverName = $this->getConnection()->getDriverName();

        if ($connectionDriverName === 'mysql') {
            /** @var Model|NestedSetModel $this */
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
            /** @var Model|NestedSetModel $this */
            $nestedSetBuilder = new NestedSetBuilder($query->getQuery());
            $nestedSetBuilder->setModel($this);

            $this->nestedSetNeedToSubstituteBuilder = false;

            return $nestedSetBuilder;
        }

        return parent::setKeysForSaveQuery($query);
    }

    /**
     * Определяет, используется ли модель мягкое удаление.
     *
     * @return bool
     */
    public function nestedSetHasSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses($this), true);
    }
}
