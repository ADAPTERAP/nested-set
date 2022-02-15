<?php

namespace Adapterap\NestedSet\Tests\Models;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Adapterap\NestedSet\Tests\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class Menu
 *
 * @package Adapterap\NestedSet\Tests\Models
 *
 * @property-read int $id
 * @property string $name
 *
 * @method static MenuFactory factory()
 */
class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'default';

    /**
     * Создает таблицу.
     */
    public static function createTable(): void
    {
        $schema = Manager::schema('default');

        if ($schema->hasTable('menus')) {
            $schema->disableForeignKeyConstraints();
            Manager::table('menus')->truncate();

            $schema->drop('menus');
            $schema->enableForeignKeyConstraints();
        }

        $schema->create('menus', static function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return new MenuFactory();
    }
}
