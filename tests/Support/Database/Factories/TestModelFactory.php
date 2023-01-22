<?php

namespace Stfn\PendingUpdates\Tests\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Stfn\PendingUpdates\Tests\Support\Models\TestModel;

class TestModelFactory extends Factory
{
    protected $model = TestModel::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'address' => $this->faker->address,
            'secret' => $this->faker->password,
        ];
    }
}
