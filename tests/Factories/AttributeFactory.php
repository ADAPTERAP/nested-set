<?php

namespace Adapterap\NestedSet\Tests\Factories;

use Adapterap\NestedSet\Tests\Models\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AttributeFactory.
 *
 * @method Attribute|Attribute[]|Collection create($attributes = [], ?Model $parent = null)
 * @method Attribute                        createOne($attributes = [])
 * @method Attribute[]|Collection           createMany(iterable $records)
 * @method Attribute[]|Collection           createChildren(Model $model)
 * @method Attribute|Attribute[]|Collection make($attributes = [], ?Model $parent = null)
 * @method Attribute                        makeOne($attributes = [])
 * @method AttributeFactory                 state($state)
 */
class AttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attribute::class;

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
            'place' => $this->faker->randomElement([
                Attribute::PLACE_ONE,
                Attribute::PLACE_TWO,
            ]),
        ];
    }
}
