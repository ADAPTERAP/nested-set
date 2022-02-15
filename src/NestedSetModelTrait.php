<?php

namespace Adapterap\NestedSet;

use Adapterap\NestedSet\Drivers\MySqlDriver;
use Adapterap\NestedSet\Drivers\NestedSetDriver;
use Adapterap\NestedSet\Exceptions\NestedSetDriverNotSupported;
use Adapterap\NestedSet\Traits\Attributes;
use Adapterap\NestedSet\Traits\Mutators;
use Adapterap\NestedSet\Traits\Relations;
use Adapterap\NestedSet\Traits\ReplaceEloquent;
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
trait NestedSetModelTrait
{
    use Attributes, Subscriber, Relations, Tree, Scopes, Mutators, ReplaceEloquent;

    /**
     * Драйвер для работы с БД.
     *
     * @var NestedSetDriver
     */
    public NestedSetDriver $nestedSetDriver;

    /**
     * Инициализация трейта.
     */
    protected function initializeNestedSetModelTrait(): void
    {
        $connectionDriverName = $this->getConnection()->getDriverName();

        $this->nestedSetDriver = $this->getNestedSetDriver($connectionDriverName);
    }

    /**
     * Возвращает драйвер для работы с БД.
     *
     * @param string $connectionName
     *
     * @return NestedSetDriver
     */
    protected function getNestedSetDriver(string $connectionName): NestedSetDriver
    {
        if ($connectionName === 'mysql') {
            /** @var Model|NestedSetModelTrait $this */
            return new MySqlDriver($this);
        }

        throw new NestedSetDriverNotSupported($connectionName);
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

    /**
     * @return Builder
     */
    public function newScopedQuery(): Builder
    {
        $result = $this->newQuery();

        if (method_exists($result, 'prepareBuilder')) {
            $result->prepareBuilder();
        }

        return $result;
    }
}
