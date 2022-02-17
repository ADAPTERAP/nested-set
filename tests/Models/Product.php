<?php

namespace Adapterap\NestedSet\Tests\Models;

use Adapterap\NestedSet\NestedSetModelTrait;
use Adapterap\NestedSet\Tests\Factories\ProductFactory;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class Category.
 *
 * @method static ProductFactory factory()
 *
 * @property-read int $id
 * @property string      $name
 * @property int         $lft
 * @property int         $rgt
 * @property int         $parent_id
 * @property int         $depth
 * @property null|Carbon $deleted_at
 */
class Product extends Model
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

    protected $fillable = [
        'name',
        'parent_id',
    ];

    /**
     * The connection name for the model.
     *
     * @var null|string
     */
    protected $connection = 'default';

    /**
     * Можно ли устанавливать значение колонки с глубиной вложенности напрямую.
     *
     * @return bool
     */
    public function canSetDepthColumn(): bool
    {
        return true;
    }

    /**
     * Создает таблицу.
     */
    public static function createTable(): void
    {
        $schema = Manager::schema('default');

        $schema->dropIfExists('products');
        $schema->create('products', static function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('parent_id')
                ->nullable();
            $table->unsignedBigInteger('lft');
            $table->unsignedBigInteger('rgt');
            $table->unsignedBigInteger('depth');
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('products')
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
        return new ProductFactory();
    }
}
