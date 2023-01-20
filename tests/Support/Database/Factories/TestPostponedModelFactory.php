<?php

namespace Stfn\PostponeUpdates\Tests\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Stfn\PostponeUpdates\Tests\Support\Models\TestModel;
use Stfn\PostponeUpdates\Tests\Support\Models\TestPostponedModel;

class TestPostponedModelFactory extends Factory
{
    public $model = TestPostponedModel::class;

    public function definition()
    {
        return [
            'parent_id' => TestModel::factory(),
            'parent_type' => 'TestModel',
            'values' => [],
        ];
    }
}
