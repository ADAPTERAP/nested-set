<?php

namespace Adapterap\NestedSet\Traits;

use Adapterap\NestedSet\Exceptions\NestedSetCreateChildHasOtherScope;
use Adapterap\NestedSet\NestedSetModelTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Expression;

/**
 * Trait Subscriber.
 *
 * @mixin NestedSetModelTrait
 */
trait Subscriber
{
    /**
     * Boot-метод трейта, который подписывается на события модели.
     */
    public static function bootSubscriber(): void
    {
        static::creating(function ($model) {
            /** @var NestedSetModelTrait $model */
            $model->nestedSetBeforeCreate();
        });

        static::created(static function ($model) {
            /** @var NestedSetModelTrait $model */
            $model->nestedSetAfterCreate();
        });

        static::updating(static function ($model) {
            /** @var NestedSetModelTrait $model */
            $model->nestedSetBeforeUpdate();
        });

        static::deleting(static function ($model) {
            /** @var NestedSetModelTrait $model */
            $model->refresh();
            $model->nestedSetBeforeDelete();
        });

        static::deleted(static function ($model) {
            /** @var NestedSetModelTrait $model */
            $model->nestedSetAfterDelete();
        });

        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(static function ($model) {
                /** @var NestedSetModelTrait $model */
                $model->nestedSetAfterForceDelete();
            });
        }
    }

    /**
     * Выполняемые действия перед созданием модели.
     */
    protected function nestedSetBeforeCreate(): void
    {
        $this->nestedSetCheckScopeBeforeSave();

        $this->setRawAttributes(
            $this->nestedSetDriver->getAttributesForInsert($this->getAttributes())
        );
    }

    /**
     * Выполняемые действия после создания модели.
     */
    protected function nestedSetAfterCreate(): void
    {
        $parentId = $this->getParentId();

        // todo: Подумать как уйти от этого
        if ($this->nestedSetHasExpressionInAttributes()) {
            $this->refresh();
        }

        // Обновляем индексы вложенности, если это не рутовый элемент.
        if ($parentId !== null) {
            $primary = $this->getKey();
            $lft = $this->getLft();

            $this->nestedSetDriver->freshIndexesAfterInsert($primary, $lft);
        }
    }

    /**
     * Выполняемые действия перед обновлением модели.
     */
    protected function nestedSetBeforeUpdate(): void
    {
        if (!$this->isDirty($this->getParentIdName())) {
            return;
        }

        $this->nestedSetCheckScopeBeforeSave();

        $this->nestedSetNeedToSubstituteBuilder = true;
    }

    /**
     * Выполняемые действия перед удалением.
     */
    protected function nestedSetBeforeDelete(): void
    {
        $this->nestedSetNeedToSubstituteBuilder = true;
    }

    /**
     * Выполняемые действия после удаления модели.
     */
    protected function nestedSetAfterDelete(): void
    {
        if (!in_array(SoftDeletes::class, class_uses($this), true)) {
            $this->nestedSetDriver->freshIndexesAfterForceDelete($this->getLft(), $this->getRgt());
        }
    }

    /**
     * Выполняемые действия после жесткого удаления модели.
     */
    protected function nestedSetAfterForceDelete(): void
    {
        $this->nestedSetDriver->freshIndexesAfterForceDelete($this->getLft(), $this->getRgt());
    }

    /**
     * Определяет есть ли в атрибутах экземпляр класса Expression.
     *
     * @return bool
     */
    protected function nestedSetHasExpressionInAttributes(): bool
    {
        if (($this->attributes[$this->getLftName()] ?? null) instanceof Expression) {
            return true;
        }

        if (($this->attributes[$this->getRgtName()] ?? null) instanceof Expression) {
            return true;
        }

        return false;
    }

    /**
     * Проверяет, что scope у root и children одинаковый.
     */
    protected function nestedSetCheckScopeBeforeSave(): void
    {
        $scopes = $this->getScopeAttributes();

        if ($this->is_root || empty($scopes)) {
            return;
        }

        if ($this->parent_id !== $this->parent->id) {
            $parent = $this->newQuery()->find($this->parent_id);
        }

        foreach ($scopes as $scope) {
            if ($this->getAttribute($scope) === ($parent ?? $this->parent)->getAttribute($scope)) {
                continue;
            }

            throw new \RuntimeException(
                sprintf(
                    'Generic parameter %s for the model %s is different from parent. %s',
                    $scope,
                    self::class,
                    json_encode([
                        'currentValue' => $this->getAttribute($scope),
                        'parent' => isset($parent) ? $parent->toArray() : null,
                        '$this->parent' => $this->parent ? $this->parent->toArray() : null,
                        'parentValue' => ($parent ?? $this->parent)->getAttribute($scope),
                    ])
                )
            );

            throw new NestedSetCreateChildHasOtherScope(self::class, $scope);
        }
    }
}
