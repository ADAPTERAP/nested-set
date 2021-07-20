<?php

namespace Adapterap\NestedSet\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Adapterap\NestedSet\Tests\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return new MenuFactory();
    }
}