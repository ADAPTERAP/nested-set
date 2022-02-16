<?php

namespace Adapterap\NestedSet\Tests\Factories;

use Adapterap\NestedSet\Tests\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductFactory.
 *
 * @method Product|Product[]|Collection  create($attributes = [], ?Model $parent = null)
 * @method Product                       createOne($attributes = [])
 * @method Product[]|Collection          createMany(iterable $records)
 * @method Product[]|Collection          createChildren(Model $model)
 * @method Product|Product[]|Collection  make($attributes = [], ?Model $parent = null)
 * @method Product                       makeOne($attributes = [])
 * @method ProductFactory                state($state)
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    public function __construct($count = null, ?\Illuminate\Support\Collection $states = null, ?\Illuminate\Support\Collection $has = null, ?\Illuminate\Support\Collection $for = null, ?\Illuminate\Support\Collection $afterMaking = null, ?\Illuminate\Support\Collection $afterCreating = null, $connection = null)
    {
        parent::__construct($count, $states, $has, $for, $afterMaking, $afterCreating, $connection);
        $this->faker = \Faker\Factory::create();
    }

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
