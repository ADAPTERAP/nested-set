<?php

namespace Adapterap\NestedSet\Tests\Factories;

use Adapterap\NestedSet\Tests\Models\Menu;
use Illuminate\Database\Eloquent\Collection;
use Adapterap\NestedSet\Tests\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MenuFactory
 *
 * @package Adapterap\NestedSet\Tests\Factories
 * @method MenuItem|MenuItem[]|Collection create($attributes = [], ?Model $parent = null)
 * @method MenuItem                       createOne($attributes = [])
 * @method MenuItem[]|Collection          createMany(iterable $records)
 * @method MenuItem[]|Collection          createChildren(Model $model)
 * @method MenuItem|MenuItem[]|Collection make($attributes = [], ?Model $parent = null)
 * @method MenuItem                       makeOne($attributes = [])
 * @method MenuItemFactory                state($state)
 */
class MenuItemFactory extends Factory
{
    public function __construct($count = null, ?\Illuminate\Support\Collection $states = null, ?\Illuminate\Support\Collection $has = null, ?\Illuminate\Support\Collection $for = null, ?\Illuminate\Support\Collection $afterMaking = null, ?\Illuminate\Support\Collection $afterCreating = null, $connection = null)
    {
        parent::__construct($count, $states, $has, $for, $afterMaking, $afterCreating, $connection);
        $this->faker = \Faker\Factory::create();
    }

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MenuItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'menu_id' => Menu::factory(),
            'name' => $this->faker->uuid,
        ];
    }
}
