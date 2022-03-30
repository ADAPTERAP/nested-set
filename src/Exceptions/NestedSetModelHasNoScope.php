<?php

namespace Adapterap\NestedSet\Exceptions;

use RuntimeException;

class NestedSetModelHasNoScope extends RuntimeException
{
    /**
     * @param string $model Класс модели
     * @param string $field Поле в scope
     */
    public function __construct(string $model, string $field)
    {
        parent::__construct("Model {$model} has no scope field {$field}.");
    }
}
