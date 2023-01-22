<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use function Spatie\PestPluginTestTime\testTime;
use Stfn\PendingUpdates\Models\PendingUpdate;
use Stfn\PendingUpdates\Tests\Support\Models\TestModel;

beforeEach(function () {
    testTime()->freeze('2023-01-01 00:00:00');

    Relation::morphMap(['TestModel' => TestModel::class]);

    $this->attributes = ['name' => 'John Doe', 'secret' => 'hash'];

    $this->model = TestModel::factory()->create($this->attributes);
});

it('can keep model updated for specific number of minutes', function () {
    $this->model->postpone()
        ->keepForMinutes(3)
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('Jane Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => $this->attributes['name']])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-01 00:03:00');
});

it('can keep model updated for specific number of hours', function () {
    $this->model->postpone()
        ->keepForHours(3)
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('Jane Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => $this->attributes['name']])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-01 03:00:00');
});

it('can keep model updated for specific number of days', function () {
    $this->model->postpone()
        ->keepForDays(3)
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('Jane Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => $this->attributes['name']])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-04 00:00:00');
});

it('can postpone the update for specific number of minutes', function () {
    $this->model->postpone()
        ->delayForMinutes(10)
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Jane Doe'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBeNull();
});

it('can postpone the update for specific number of hours', function () {
    $this->model->postpone()
        ->delayForHours(12)
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Jane Doe'])
        ->start_at->toBe('2023-01-01 12:00:00')
        ->revert_at->toBeNull();
});

it('can postpone the update for specific number of days', function () {
    $this->model->postpone()
        ->delayForDays(6)
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Jane Doe'])
        ->start_at->toBe('2023-01-07 00:00:00')
        ->revert_at->toBeNull();
});

it('can postpone the update for specific number of minutes and keep it updated for some time', function () {
    $this->model->postpone()
        ->delayForMinutes(10)
        ->keepForHours(3)
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Jane Doe'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-01 03:10:00');
});

it('can use timestamp for start at', function () {
    $this->model->postpone()
        ->startFrom('2023-01-01 00:10:00')
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Jane Doe'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBeNull();
});

it('can use timestamp for revert at', function () {
    $this->model->postpone()
        ->revertAt('2023-01-01 00:10:00')
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('Jane Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'John Doe'])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-01 00:10:00');
});

it('can use timestamp for start at and revert at', function () {
    $this->model->postpone()
        ->startFrom('2023-01-01 00:10:00')
        ->revertAt('2023-01-01 03:10:00')
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Jane Doe'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-01 03:10:00');
});

it('can use timestamp in reverse order for start at and revert at', function () {
    $this->model->postpone()
        ->revertAt('2023-01-01 03:10:00')
        ->startFrom('2023-01-01 00:10:00')
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Jane Doe'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-01 03:10:00');
});

it('can use combination of timestamp and time definition', function () {
    $this->model->postpone()
        ->startFrom('2023-01-01 00:10:00')
        ->keepForDays(3)
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Jane Doe'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-04 00:10:00');
});

it('will not touch the model if the update is postponed', function () {
    testTime()->freeze('2023-01-01 03:00:00');

    $this->model->postpone()
        ->delayForMinutes(3)
        ->update(['name' => 'Jane Doe']);

    expect($this->model->fresh()->updated_at->toDateTimeString())->toBe('2023-01-01 00:00:00');
});

it('will touch the model if the update is not postponed', function () {
    testTime()->freeze('2023-01-01 03:00:00');

    $this->model->postpone()
        ->keepForMinutes(3)
        ->update(['name' => 'Jane Doe']);

    $model = $this->model->fresh();

    expect($model)
        ->updated_at->toDateTimeString()->toBe('2023-01-01 03:00:00')
        ->name->toBe('Jane Doe');
});

it('will not change behavior of update without pending attached to it', function () {
    $this->model->update(['name' => 'Jane Doe']);

    expect($this->model->fresh())->name->toBe('Jane Doe');
    expect(PendingUpdate::count())->toBe(0);
});

it('will delete pending updates on model delete', function () {
    $this->model->postpone()
        ->delayForMinutes(10)
        ->update(['name' => 'Jane Doe']);

    expect(PendingUpdate::count())->toBe(1);

    $this->model->delete();

    expect(PendingUpdate::count())->toBe(0);
});

it('will override previous pending update with the new one', function () {
    $this->model->postpone()
        ->keepForHours(3)
        ->delayForMinutes(10)
        ->update(['name' => 'Jane Doe']);

    expect(PendingUpdate::count())->toBe(1);

    expect(PendingUpdate::first())
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-01 03:10:00')
        ->values->toBe(['name' => 'Jane Doe']);

    $this->model->postpone()
        ->delayForHours(3)
        ->update(['name' => 'Henry']);

    expect(PendingUpdate::count())->toBe(1);

    expect(PendingUpdate::first())
        ->start_at->toBe('2023-01-01 03:00:00')
        ->revert_at->toBeNull()
        ->values->toBe(['name' => 'Henry']);

    expect($this->model->fresh())->name->toBe('John Doe');
});

it('can use different date format', function () {
    $this->model->postpone()
        ->startFrom('2023/12/31 00:00:00')
        ->update(['name' => 'Jane Doe']);

    expect(PendingUpdate::first())
        ->start_at->toBe('2023-12-31 00:00:00');
});

it('will not save anything if attributes are not changed', function () {
    $this->model->postpone()
        ->keepForMinutes(3)
        ->update(['name' => 'John Doe']);

    expect(TestModel::first())->name->toBe('John Doe');
    expect(PendingUpdate::count())->toBe(0);
});
