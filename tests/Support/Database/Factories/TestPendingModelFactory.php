<?php

namespace Stfn\PendingUpdates\Tests\Support\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Stfn\PendingUpdates\Tests\Support\Models\TestModel;
use Stfn\PendingUpdates\Tests\Support\Models\TestPendingModel;

class TestPendingModelFactory extends Factory
{
    public $model = TestPendingModel::class;

    public function definition()
    {
        return [
            'parent_id' => TestModel::factory(),
            'parent_type' => 'TestModel',
            'values' => [],
            'is_confirmed' => true,
        ];
    }
}
