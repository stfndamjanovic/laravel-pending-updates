<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use function Pest\Laravel\artisan;
use function Spatie\PestPluginTestTime\testTime;
use Stfn\PendingUpdates\Commands\CheckPendingUpdates;
use Stfn\PendingUpdates\Models\PendingUpdate;
use Stfn\PendingUpdates\Tests\Support\Models\TestModel;
use Stfn\PendingUpdates\Tests\Support\Models\TestPendingModel;
use Illuminate\Database\QueryException;

beforeEach(function () {
    testTime()->freeze('2023-01-01 00:00:00');

    Relation::morphMap([
        'TestModel' => TestModel::class,
    ]);

    $this->attributes = ['name' => 'John Doe', 'secret' => 'hash'];

    $this->model = TestModel::factory()->create($this->attributes);
});

it('can revert changes', function () {
    TestPendingModel::factory()->create([
        'revert_at' => '2023-01-02 00:00:00',
        'parent_id' => $this->model->id,
        'values' => ['name' => 'John Doe Original'],
    ]);

    $testModel = TestModel::factory()->create(['name' => 'Updated Name']);

    TestPendingModel::factory()->create([
        'revert_at' => '2022-12-31 23:00:00',
        'parent_id' => $testModel->id,
        'values' => ['name' => 'Original Name'],
    ]);

    artisan(CheckPendingUpdates::class)->assertSuccessful();

    expect($this->model->fresh())->name->toBe('John Doe');
    expect($testModel->fresh())->name->toBe('Original Name');
    expect(PendingUpdate::count())->toBe(1);
    expect(PendingUpdate::first())->values->toBe(['name' => 'John Doe Original']);
});

it('can start to apply delayed changes', function () {
    TestPendingModel::factory()->create([
        'start_at' => '2022-12-31 23:59:59',
        'parent_id' => $this->model->id,
        'values' => ['name' => 'John Doe Delayed'],
    ]);

    artisan(CheckPendingUpdates::class)->assertSuccessful();

    expect($this->model->fresh())->name->toBe('John Doe Delayed');
    expect(PendingUpdate::count())->toBe(0);
});

it('can start to apply delayed changes and revert that after some time', function () {
    TestPendingModel::factory()->create([
        'start_at' => '2022-12-31 23:59:59',
        'revert_at' => '2023-01-01 02:59:59',
        'parent_id' => $this->model->id,
        'values' => ['name' => 'John Doe Delayed'],
    ]);

    artisan(CheckPendingUpdates::class)->assertSuccessful();

    expect($this->model->fresh())->name->toBe('John Doe Delayed');
    expect(PendingUpdate::count())->toBe(1);
    expect(PendingUpdate::first())->values->toBe(['name' => 'John Doe']);

    testTime()->addHours(3);

    artisan(CheckPendingUpdates::class)->assertSuccessful();

    expect($this->model->fresh())->name->toBe('John Doe');
    expect(PendingUpdate::count())->toBe(0);
});

it('will remove postpone update if model is deleted in the meantime', function () {
    TestPendingModel::factory()->create([
        'start_at' => '2022-12-31 23:59:59',
        'parent_id' => $this->model->id,
    ]);

    TestPendingModel::factory()->create([
        'revert_at' => '2022-12-31 23:59:59',
        'parent_id' => $this->model->id,
    ]);

    $this->model->deleteQuietly();

    artisan(CheckPendingUpdates::class)->assertSuccessful();

    expect(PendingUpdate::count())->toBe(0);
});

it('will not save anything to postponed_updates if model update fail', function () {
    try {
        $this->model->postpone()
            ->keepForMinutes(10)
            ->update(['name' => null]);
    } catch (QueryException $exception) {
    }

    expect($this->model->fresh())->name->toBe('John Doe');
    expect(PendingUpdate::count())->toBe(0);

    try {
        $this->model->postpone()
            ->delayForMinutes(10)
            ->update(['name' => null]);
    } catch (QueryException $exception) {
    }

    expect($this->model->fresh())->name->toBe('John Doe');
    expect(PendingUpdate::count())->toBe(1);

    testTime()->addHour();

    artisan(CheckPendingUpdates::class)->assertSuccessful();

    // Name is not reverted because name cannot be null
    expect($this->model->fresh())->name->toBe('John Doe');
    expect(PendingUpdate::count())->toBe(0);
});

it('will remove pending update if it cannot be applied on original table', function () {
    TestPendingModel::factory()->create([
        'revert_at' => '2022-12-31 23:59:59',
        'parent_id' => $this->model->id,
        'values' => ['unknown_field' => null]
    ]);

    artisan(CheckPendingUpdates::class)->assertSuccessful();

    expect($this->model->fresh())->name->toBe('John Doe');
    expect(PendingUpdate::count())->toBe(0);
});
