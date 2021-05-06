<?php

namespace Adapterap\NestedSet\Tests\Models;

use Adapterap\NestedSet\NestedSetModelTrait;
use Adapterap\NestedSet\Tests\Factories\CategoryFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Category
 *
 * @package Adapterap\NestedSet\Tests\Models
 * @method static CategoryFactory factory()
 * @property-read int $id
 * @property string $name
 * @property int $lft
 * @property int $rgt
 * @property int $parent_id
 * @property int $depth
 * @property Carbon|null $deleted_at
 */
class Category extends Model
{
    use NestedSetModelTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'parent_id',
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
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return new CategoryFactory();
    }
}
