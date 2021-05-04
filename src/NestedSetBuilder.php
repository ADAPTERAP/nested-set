<?php

namespace Adapterap\NestedSet;

use Illuminate\Database\Eloquent\Builder;

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
        /** @var NestedSet $model */
        $model = $this->getModel();

        return $model->nestedSetDriver->rebaseSubTree(
            $model->getOriginal($model->getKeyName()) ?? $model->getAttribute($model->getKeyName()),
            $model->getAttribute($model->parentIdName),
            $values
        );
    }
}
