<?php

namespace Adapterap\NestedSet\Builders;

use Adapterap\NestedSet\NestedSetModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NestedSetBuilder extends Builder
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
        /** @var NestedSetModel|Model|SoftDeletes $model */
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
        /** @var NestedSetModel $model */
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
        /** @var NestedSetModel $model */
        $model = $this->getModel();

        return $model->nestedSetDriver->forceDelete($model->getKey());
    }
}
