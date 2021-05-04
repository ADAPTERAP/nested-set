<?php

namespace Adapterap\NestedSet\Exceptions;

use RuntimeException;

class NestedSetDriverNotSupported extends RuntimeException
{
    /**
     * NestedSetDriverNotSupported constructor.
     *
     * @param string $driverName
     */
    public function __construct(string $driverName)
    {
        parent::__construct("Driver [{$driverName}] is not supported for NestedSet");
    }
}
