<?php

namespace Adapterap\NestedSet\Tests\Factories;

use Adapterap\NestedSet\Tests\Models\Menu;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MenuFactory.
 *
 * @method Collection|Menu|Menu[] create($attributes = [], ?Model $parent = null)
 * @method Menu                   createOne($attributes = [])
 * @method Collection|Menu[]      createMany(iterable $records)
 * @method Collection|Menu[]      createChildren(Model $model)
 * @method Collection|Menu|Menu[] make($attributes = [], ?Model $parent = null)
 * @method Menu                   makeOne($attributes = [])
 * @method MenuFactory            state($state)
 */
class MenuFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Menu::class;

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
