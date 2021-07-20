<?php

namespace Adapterap\NestedSet\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Adapterap\NestedSet\NestedSetModelTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Adapterap\NestedSet\Tests\Factories\MenuItemFactory;

/**
 * Class MenuItem
 *
 * @package Adapterap\NestedSet\Tests\Models
 *
 * @property-read int $id
 * @property int      $menu_id
 * @property int      $parent_id
 * @property string   $name
 *
 * @method static MenuItemFactory factory(...$parameters)
 */
class MenuItem extends Model
{
    use NestedSetModelTrait, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'menu_id',
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
     * Возвращает массив полей объединяющих узлы
     *
     * @return array
     */
    public function getScopeAttributes(): array
    {
        return ['menu_id'];
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