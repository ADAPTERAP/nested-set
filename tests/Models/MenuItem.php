<?php

namespace Adapterap\NestedSet\Tests\Models;

use Adapterap\NestedSet\Contracts\NestedSetModel;
use Adapterap\NestedSet\NestedSetModelTrait;
use Adapterap\NestedSet\Tests\Factories\MenuItemFactory;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class MenuItem.
 *
 * @property-read int $id
 * @property int    $menu_id
 * @property int    $parent_id
 * @property string $name
 *
 * @method static MenuItemFactory factory(...$parameters)
 */
class MenuItem extends Model implements NestedSetModel
{
    use NestedSetModelTrait;
    use HasFactory;
    use SoftDeletes;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'menu_id',
        'parent_id',
    ];

    /**
     * The connection name for the model.
     *
     * @var null|string
     */
    protected $connection = 'default';

    /**
     * Возвращает массив полей объединяющих узлы.
     *
     * @return array
     */
    public function getScopeAttributes(): array
    {
        return ['menu_id'];
    }

    /**
     * Создает таблицу.
     */
    public static function createTable(): void
    {
        $schema = Manager::schema('default');

        if ($schema->hasTable('menu_items')) {
            $schema->disableForeignKeyConstraints();
            Manager::table('menu_items')->truncate();

            $schema->drop('menu_items');
            $schema->enableForeignKeyConstraints();
        }

        $schema->create('menu_items', static function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('parent_id')
                ->nullable();
            $table->unsignedBigInteger('lft');
            $table->unsignedBigInteger('rgt');
            $table->unsignedBigInteger('depth');
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('menu_items')
                ->cascadeOnDelete();

            $table->foreign('menu_id')
                ->references('id')
                ->on('menus')
                ->cascadeOnDelete();
        });
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return new MenuItemFactory();
    }
}
