<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use function Spatie\PestPluginTestTime\testTime;
use Stfn\PendingUpdates\Models\PendingUpdate;
use Stfn\PendingUpdates\Tests\Support\Models\TestModel;
use function Pest\Laravel\artisan;
use Stfn\PendingUpdates\Commands\CheckPendingUpdates;

beforeEach(function () {
    testTime()->freeze('2023-01-01 00:00:00');

    Relation::morphMap(['TestModel' => TestModel::class]);

    $this->attributes = ['name' => 'John Doe', 'secret' => 'hash'];

    $this->model = TestModel::factory()->create($this->attributes);
});

it('can keep model updated for specific number of minutes', function () {
    $this->model->pending()
        ->keepForMinutes(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('Stefan');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => $this->attributes['name']])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-01 00:03:00');
});

it('can keep model updated for specific number of hours', function () {
    $this->model->pending()
        ->keepForHours(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('Stefan');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => $this->attributes['name']])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-01 03:00:00');
});

it('can keep model updated for specific number of days', function () {
    $this->model->pending()
        ->keepForDays(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('Stefan');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => $this->attributes['name']])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-04 00:00:00');
});

it('can postpone the update for specific number of minutes', function () {
    $this->model->pending()
        ->delayForMinutes(10)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBeNull();
});

it('can postpone the update for specific number of hours', function () {
    $this->model->pending()
        ->delayForHours(12)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 12:00:00')
        ->revert_at->toBeNull();
});

it('can postpone the update for specific number of days', function () {
    $this->model->pending()
        ->delayForDays(6)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-07 00:00:00')
        ->revert_at->toBeNull();
});

it('can postpone the update for specific number of minutes and keep it updated for some time', function () {
    $this->model->pending()
        ->delayForMinutes(10)
        ->keepForHours(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-01 03:10:00');
});

it('can use timestamp for start at', function () {
    $this->model->pending()
        ->startFrom('2023-01-01 00:10:00')
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBeNull();
});

it('can use timestamp for revert at', function () {
    $this->model->pending()
        ->revertAt('2023-01-01 00:10:00')
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('Stefan');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'John Doe'])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-01 00:10:00');
});

it('can use timestamp for start at and revert at', function () {
    $this->model->pending()
        ->startFrom('2023-01-01 00:10:00')
        ->revertAt('2023-01-01 03:10:00')
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-01 03:10:00');
});

it('can use timestamp in reverse order for start at and revert at', function () {
    $this->model->pending()
        ->revertAt('2023-01-01 03:10:00')
        ->startFrom('2023-01-01 00:10:00')
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-01 03:10:00');
});

it('can use combination of timestamp and time definition', function () {
    $this->model->pending()
        ->startFrom('2023-01-01 00:10:00')
        ->keepForDays(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PendingUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-04 00:10:00');
});

it('will not touch the model if the update is postponed', function () {
    testTime()->freeze('2023-01-01 03:00:00');

    $this->model->pending()
        ->delayForMinutes(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh()->updated_at->toDateTimeString())->toBe('2023-01-01 00:00:00');
});

it('will touch the model if the update is not postponed', function () {
    testTime()->freeze('2023-01-01 03:00:00');

    $this->model->pending()
        ->keepForMinutes(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh()->updated_at->toDateTimeString())->toBe('2023-01-01 03:00:00');
});

it('will not change behavior of update without postpone', function () {
    $this->model->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('Stefan');
    expect(PendingUpdate::count())->toBe(0);
});

it('will delete postponed updates on model delete', function () {
    $this->model->pending()
        ->delayForMinutes(10)
        ->update(['name' => 'Stefan']);

    expect(PendingUpdate::count())->toBe(1);

    $this->model->delete();

    expect(PendingUpdate::count())->toBe(0);
});

it('will override previous postponed update with the new one', function () {
    $this->model->pending()
        ->keepForHours(3)
        ->delayForMinutes(10)
        ->update(['name' => 'Stefan']);

    expect(PendingUpdate::count())->toBe(1);

    expect(PendingUpdate::first())
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-01 03:10:00')
        ->values->toBe(['name' => 'Stefan']);

    $this->model->pending()
        ->delayForHours(3)
        ->update(['name' => 'Dragan']);

    expect(PendingUpdate::count())->toBe(1);

    expect(PendingUpdate::first())
        ->start_at->toBe('2023-01-01 03:00:00')
        ->revert_at->toBeNull()
        ->values->toBe(['name' => 'Dragan']);

    expect($this->model->fresh())->name->toBe('John Doe');
});

it('can use different date format', function () {
    $this->model->pending()
        ->startFrom('2023/12/31 00:00:00')
        ->update(['name' => 'Stefan']);

    expect(PendingUpdate::first())
        ->start_at->toBe('2023-12-31 00:00:00');
});

it('will not save anything to postponed_updates if model update fail', function () {
    try {
        $this->model->pending()
            ->keepForMinutes(10)
            ->update(['name' => null]);
    } catch (QueryException $exception) {
    }

    expect($this->model->fresh())->name->toBe('John Doe');
    expect(PendingUpdate::count())->toBe(0);

    try {
        $this->model->pending()
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
