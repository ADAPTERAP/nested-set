<?php

namespace Adapterap\NestedSet\Tests\Models;

use Adapterap\NestedSet\NestedSetModelTrait;
use Adapterap\NestedSet\Tests\Factories\AttributeFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Attribute
 *
 * @package Adapterap\NestedSet\Tests\Models
 * @property int $id
 * @property string $name
 * @property int $place
 * @property int|null $parent_id
 * @property int $lft
 * @property int $rgt
 * @property int $depth
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class Attribute extends Model
{
    use SoftDeletes, HasFactory, NestedSetModelTrait;

    public const PLACE_ONE = 1;
    public const PLACE_TWO = 2;

    /**
     * The connection name for the model.
     *
     * @var string|null
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
     * Возвращает названия полей по которым необходимо сгруппировать деревья.
     *
     * @return array
     */
    public function getNestedGroupBy(): array
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
