<?php

namespace Adapterap\NestedSet\Tests\Factories;

use Adapterap\NestedSet\Tests\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class CategoryFactory
 *
 * @package Adapterap\NestedSet\Tests\Factories
 * @method Category|Category[]|Collection create($attributes = [], ?Model $parent = null)
 * @method Category                       createOne($attributes = [])
 * @method Category[]|Collection      createMany(iterable $records)
 * @method Category[]|Collection      createChildren(Model $model)
 * @method Category|Category[]|Collection make($attributes = [], ?Model $parent = null)
 * @method Category                       makeOne($attributes = [])
 * @method CategoryFactory                state($state)
 */
class CategoryFactory extends Factory
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
    protected $model = Category::class;

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
