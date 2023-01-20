<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use function Pest\Laravel\artisan;
use function Spatie\PestPluginTestTime\testTime;
use Stfn\PostponeUpdates\Commands\CheckPostponedUpdates;
use Stfn\PostponeUpdates\Models\PostponedUpdate;
use Stfn\PostponeUpdates\Tests\Support\Models\TestModel;
use Stfn\PostponeUpdates\Tests\Support\Models\TestPostponedModel;

beforeEach(function () {
    testTime()->freeze('2023-01-01 00:00:00');

    Relation::morphMap([
        'TestModel' => TestModel::class,
    ]);

    $this->attributes = ['name' => 'John Doe', 'secret' => 'hash'];

    $this->model = TestModel::factory()->create($this->attributes);
});

it('can revert changes', function () {
    TestPostponedModel::factory()->create([
        'revert_at' => '2023-01-02 00:00:00',
        'parent_id' => $this->model->id,
        'values' => ['name' => 'John Doe Original'],
    ]);

    $testModel = TestModel::factory()->create(['name' => 'Updated Name']);

    TestPostponedModel::factory()->create([
        'revert_at' => '2022-12-31 23:00:00',
        'parent_id' => $testModel->id,
        'values' => ['name' => 'Original Name'],
    ]);

    artisan(CheckPostponedUpdates::class)->assertSuccessful();

    expect($this->model->fresh())->name->toBe('John Doe');
    expect($testModel->fresh())->name->toBe('Original Name');
    expect(PostponedUpdate::count())->toBe(1);
    expect(PostponedUpdate::first())->values->toBe(['name' => 'John Doe Original']);
});

it('can start to apply delayed changes', function () {
    TestPostponedModel::factory()->create([
        'start_at' => '2022-12-31 23:59:59',
        'parent_id' => $this->model->id,
        'values' => ['name' => 'John Doe Delayed'],
    ]);

    artisan(CheckPostponedUpdates::class)->assertSuccessful();

    expect($this->model->fresh())->name->toBe('John Doe Delayed');
    expect(PostponedUpdate::count())->toBe(0);
});

it('can start to apply delayed changes and revert that after some time', function () {
    TestPostponedModel::factory()->create([
        'start_at' => '2022-12-31 23:59:59',
        'revert_at' => '2023-01-01 02:59:59',
        'parent_id' => $this->model->id,
        'values' => ['name' => 'John Doe Delayed'],
    ]);

    artisan(CheckPostponedUpdates::class)->assertSuccessful();

    expect($this->model->fresh())->name->toBe('John Doe Delayed');
    expect(PostponedUpdate::count())->toBe(1);
    expect(PostponedUpdate::first())->values->toBe(['name' => 'John Doe']);

    testTime()->addHours(3);

    artisan(CheckPostponedUpdates::class)->assertSuccessful();

    expect($this->model->fresh())->name->toBe('John Doe');
    expect(PostponedUpdate::count())->toBe(0);
});
