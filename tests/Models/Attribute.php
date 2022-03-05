<?php

namespace Adapterap\NestedSet\Tests\Models;

use Adapterap\NestedSet\Contracts\NestedSetModel;
use Adapterap\NestedSet\NestedSetModelTrait;
use Adapterap\NestedSet\Tests\Factories\AttributeFactory;
use Carbon\Carbon;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class Attribute.
 *
 * @property int         $id
 * @property string      $name
 * @property int         $place
 * @property null|int    $parent_id
 * @property int         $lft
 * @property int         $rgt
 * @property int         $depth
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 * @property null|Carbon $deleted_at
 */
class Attribute extends Model implements NestedSetModel
{
    use SoftDeletes;
    use HasFactory;
    use NestedSetModelTrait;

    public const PLACE_ONE = 1;
    public const PLACE_TWO = 2;

    /**
     * The connection name for the model.
     *
     * @var null|string
     */
    protected $connection = 'default';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'place',
        'parent_id',
        'lft',
        'rgt',
        'depth',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'place' => 'integer',
        'parent_id' => 'integer',
        'lft' => 'integer',
        'rgt' => 'integer',
        'depth' => 'integer',
    ];

    /**
     * Создает таблицу.
     */
    public static function createTable(): void
    {
        $schema = Manager::schema('default');

        $schema->dropIfExists('attributes');
        $schema->create('attributes', static function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('place');
            $table->unsignedBigInteger('parent_id')
                ->nullable();
            $table->unsignedBigInteger('lft');
            $table->unsignedBigInteger('rgt');
            $table->unsignedBigInteger('depth');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')
                ->references('id')
                ->on('attributes')
                ->cascadeOnDelete();
        });
    }

    /**
     * Возвращает названия полей по которым необходимо сгруппировать деревья.
     *
     * @return array
     */
    public function getScopeAttributes(): array
    {
        return ['place'];
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return AttributeFactory
     */
    protected static function newFactory(): AttributeFactory
    {
        return new AttributeFactory();
    }
}
