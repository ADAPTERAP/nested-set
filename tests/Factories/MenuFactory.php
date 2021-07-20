<?php

namespace Adapterap\NestedSet\Tests\Factories;

use Adapterap\NestedSet\Tests\Models\Menu;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MenuFactory
 *
 * @package Adapterap\NestedSet\Tests\Factories
 * @method Menu|Menu[]|Collection create($attributes = [], ?Model $parent = null)
 * @method Menu                   createOne($attributes = [])
 * @method Menu[]|Collection      createMany(iterable $records)
 * @method Menu[]|Collection      createChildren(Model $model)
 * @method Menu|Menu[]|Collection make($attributes = [], ?Model $parent = null)
 * @method Menu                   makeOne($attributes = [])
 * @method MenuFactory            state($state)
 */
class MenuFactory extends Factory
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
    protected $model = Menu::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->uuid,
        ];
    }
}
