<?php

namespace Adapterap\NestedSet\Tests\Models;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Support\Facades\App;

abstract class Model extends \Illuminate\Database\Eloquent\Model
{
    /**
     * Настраивает соединение с БД.
     */
    public static function setUpConnection(): void
    {
        static::setConnectionResolver(
            new ConnectionResolver(['default' => Manager::connection('default')])
        );

        static::getConnectionResolver()->setDefaultConnection('default');
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public static function table(): string
    {
        return App::make(static::class)->getTable();
    }
}
