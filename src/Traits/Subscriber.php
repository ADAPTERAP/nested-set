<?php

namespace Adapterap\NestedSet\Traits;

use Adapterap\NestedSet\NestedSet;
use Illuminate\Database\Query\Expression;

/**
 * Trait Subscriber
 *
 * @package Adapterap\NestedSet\Traits
 * @mixin NestedSet
 */
trait Subscriber
{
    /**
     * Boot-метод трейта, который подписывается на события модели.
     */
    public static function bootSubscriber(): void
    {
        static::creating(function ($model) {
            /** @var NestedSet $model */
            $model->nestedSetBeforeCreate();
        });

        static::created(static function ($model) {
            /** @var NestedSet $model */
            $model->nestedSetAfterCreate();
        });

        static::updating(static function ($model) {
            /** @var NestedSet $model */
            $model->nestedSetBeforeUpdate();
        });

        static::deleting(static function ($model) {
            /** @var NestedSet $model */
            $model->refresh();
            $model->nestedSetBeforeDelete();
        });

        static::deleted(static function ($model) {
            /** @var NestedSet $model */
            $model->nestedSetAfterDelete();
        });
    }

    /**
     * Выполняемые действия перед созданием модели.
     *
     * @return void
     */
    protected function nestedSetBeforeCreate(): void
    {
        $this->setRawAttributes(
            $this->nestedSetDriver->getAttributesForInsert($this->getAttributes())
        );
    }

    /**
     * Выполняемые действия после создания модели.
     *
     * @return void
     */
    protected function nestedSetAfterCreate(): void
    {
        $parentId = $this->getAttribute($this->parentIdName);

        // todo: Подумать как уйти от этого
        if ($this->nestedSetHasExpressionInAttributes()) {
            $this->refresh();
        }

        // Обновляем индексы вложенности, если это не рутовый элемент.
        if ($parentId !== null) {
            $primary = $this->getAttribute($this->getKeyName());
            $lft = $this->getAttribute($this->lftName);

            $this->nestedSetDriver->freshIndexesAfterInsert($primary, $lft);
        }
    }

    /**
     * Выполняемые действия перед обновлением модели.
     *
     * @return void
     */
    protected function nestedSetBeforeUpdate(): void
    {
        if (!$this->isDirty($this->parentIdName)) {
            return;
        }

        static::$nestedSetNeedToSubstitudeBuilder = true;
    }

    /**
     * Выполняемые действия перед удалением.
     */
    protected function nestedSetBeforeDelete(): void
    {
        static::$nestedSetNeedToSubstitudeBuilder = true;
    }

    /**
     * Выполняемые действия после удаления модели.
     */
    protected function nestedSetAfterDelete(): void
    {
        $this->nestedSetDriver->freshIndexesAfterDelete(
            $this->getAttribute($this->lftName),
            $this->getAttribute($this->rgtName)
        );
    }

    /**
     * Определяет есть ли в атрибутах экземпляр класса Expression
     *
     * @return bool
     */
    protected function nestedSetHasExpressionInAttributes(): bool
    {
        if ($this->getAttribute($this->lftName) instanceof Expression) {
            return true;
        }

        if ($this->getAttribute($this->rgtName) instanceof Expression) {
            return true;
        }

        return false;
    }
}
