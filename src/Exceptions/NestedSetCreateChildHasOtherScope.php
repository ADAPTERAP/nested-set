<?php

namespace Adapterap\NestedSet\Exceptions;

use RuntimeException;

class NestedSetCreateChildHasOtherScope extends RuntimeException
{
    /**
     * NestedSetCreateChildHasOtherScope constructor.
     *
     * @param string $model Класс модели
     * @param string $field Поле в scope
     */
    public function __construct(string $model, string $field)
    {
        parent::__construct("Generic parameter {$field} for the model {$model} is different from parent.");
    }
}
