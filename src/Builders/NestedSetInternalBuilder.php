<?php

namespace Adapterap\NestedSet\Builders;

use Adapterap\NestedSet\NestedSetModelTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NestedSetInternalBuilder extends Builder
{
    /**
     * Update records in the database.
     *
     * @param array $values
     *
     * @return int
     */
    public function update(array $values): int
    {
        /** @var NestedSetModelTrait|Model|SoftDeletes $model */
        $model = $this->getModel();

        if (in_array(SoftDeletes::class, class_uses($model), true)) {
            $deletedAtName = $model->getDeletedAtColumn();
            if (!empty($values[$deletedAtName])) {
                return (int)$this->delete();
            }
        }

        return $model->nestedSetDriver->rebaseSubTree(
            $model->getOriginal($model->getKeyName()) ?? $model->getKey(),
            $model->getParentId(),
            $values
        );
    }

    /**
     * Delete records from the database.
     *
     * @return bool
     */
    public function delete(): bool
    {
        /** @var NestedSetModelTrait $model */
        $model = $this->getModel();

        if (in_array(SoftDeletes::class, class_uses($model), true)) {
            return $model->nestedSetDriver->softDelete($model->getKey());
        }

        return $this->forceDelete();
    }

    /**
     * Delete records from the database.
     *
     * @return bool
     */
    public function forceDelete(): bool
    {
        /** @var NestedSetModelTrait $model */
        $model = $this->getModel();

        return $model->nestedSetDriver->forceDelete($model->getKey());
    }
}
