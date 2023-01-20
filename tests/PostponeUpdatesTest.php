<?php

use Illuminate\Database\Eloquent\Relations\Relation;
use function Spatie\PestPluginTestTime\testTime;
use Stfn\PostponeUpdates\Exceptions\InvalidPostponeParametersException;
use Stfn\PostponeUpdates\Models\PostponedUpdate;
use Stfn\PostponeUpdates\Tests\Support\Models\TestModel;
use Carbon\Exceptions\InvalidFormatException;

beforeEach(function () {
    testTime()->freeze('2023-01-01 00:00:00');

    Relation::morphMap(['TestModel' => TestModel::class]);

    $this->attributes = ['name' => 'John Doe', 'secret' => 'hash'];

    $this->model = TestModel::factory()->create($this->attributes);
});

it('can keep model updated for specific number of minutes', function () {
    $this->model->postponer()
        ->keepForMinutes(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('Stefan');

    $delayedAction = PostponedUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => $this->attributes['name']])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-01 00:03:00');
});

it('can keep model updated for specific number of hours', function () {
    $this->model->postponer()
        ->keepForHours(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('Stefan');

    $delayedAction = PostponedUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => $this->attributes['name']])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-01 03:00:00');
});

it('can keep model updated for specific number of days', function () {
    $this->model->postponer()
        ->keepForDays(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('Stefan');

    $delayedAction = PostponedUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => $this->attributes['name']])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-04 00:00:00');
});

it('can postpone the update for specific number of minutes', function () {
    $this->model->postponer()
        ->delayForMinutes(10)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PostponedUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBeNull();
});

it('can postpone the update for specific number of hours', function () {
    $this->model->postponer()
        ->delayForHours(12)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PostponedUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 12:00:00')
        ->revert_at->toBeNull();
});

it('can postpone the update for specific number of days', function () {
    $this->model->postponer()
        ->delayForDays(6)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PostponedUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-07 00:00:00')
        ->revert_at->toBeNull();
});

it('can postpone the update for specific number of minutes and keep it updated for some time', function () {
    $this->model->postponer()
        ->delayForMinutes(10)
        ->keepForHours(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PostponedUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-01 03:10:00');
});

it('can use timestamp for start at', function () {
    $this->model->postponer()
        ->startFrom('2023-01-01 00:10:00')
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PostponedUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBeNull();
});

it('can use timestamp for revert at', function () {
    $this->model->postponer()
        ->revertAt('2023-01-01 00:10:00')
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('Stefan');

    $delayedAction = PostponedUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'John Doe'])
        ->start_at->toBeNull()
        ->revert_at->toBe('2023-01-01 00:10:00');
});

it('can use timestamp start at and revert at', function () {
    $this->model->postponer()
        ->startFrom('2023-01-01 00:10:00')
        ->revertAt('2023-01-01 03:10:00')
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PostponedUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-01 03:10:00');
});

it('can use combination of timestamp and time definition', function () {
    $this->model->postponer()
        ->startFrom('2023-01-01 00:10:00')
        ->keepForDays(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('John Doe');

    $delayedAction = PostponedUpdate::first();

    expect($delayedAction)
        ->values->toBe(['name' => 'Stefan'])
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-04 00:10:00');
});

it('will not touch the model if the update is postponed', function () {
    testTime()->freeze('2023-01-01 03:00:00');

    $this->model->postponer()
        ->delayForMinutes(3)
        ->update(['name' => 'Stefan']);

    expect($this->model->fresh()->updated_at->toDateTimeString())->toBe('2023-01-01 00:00:00');
});

it('will not change behavior of update without postpone', function () {
    $this->model->update(['name' => 'Stefan']);

    expect($this->model->fresh())->name->toBe('Stefan');
    expect(PostponedUpdate::count())->toBe(0);
});

it('will delete postponed updates on model delete', function () {
    $this->model->postponer()
        ->delayForMinutes(10)
        ->update(['name' => 'Stefan']);

    expect(PostponedUpdate::count())->toBe(1);

    $this->model->delete();

    expect(PostponedUpdate::count())->toBe(0);
});

it('will override previous postponed update with the new one', function () {
    $this->model->postponer()
        ->keepForHours(3)
        ->delayForMinutes(10)
        ->update(['name' => 'Stefan']);

    expect(PostponedUpdate::count())->toBe(1);

    expect(PostponedUpdate::first())
        ->start_at->toBe('2023-01-01 00:10:00')
        ->revert_at->toBe('2023-01-01 03:10:00')
        ->values->toBe(['name' => 'Stefan']);

    $this->model->postponer()
        ->delayForHours(3)
        ->update(['name' => 'Dragan']);

    expect(PostponedUpdate::count())->toBe(1);

    expect(PostponedUpdate::first())
        ->start_at->toBe('2023-01-01 03:00:00')
        ->revert_at->toBeNull()
        ->values->toBe(['name' => 'Dragan']);

    expect($this->model->fresh())->name->toBe('John Doe');
});

it('can use different date format', function () {
    $this->model->postponer()
        ->startFrom("2023/12/31 00:00:00")
        ->update(['name' => 'Stefan']);

    expect(PostponedUpdate::first())
        ->start_at->toBe('2023-12-31 00:00:00');
});

it('will throw an exception if postponer is called on any method other than update', function () {
    $this->model->postponer()
        ->delayForHours(10)
        ->delete();
})->throws(BadMethodCallException::class);

it('will fail if postpone parameters are not set', function () {
    $this->model->postponer()
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);

it('will fail if start from is grater than revert at date', function () {
    $this->model->postponer()
        ->startFrom('2023-01-01 03:00:01')
        ->revertAt('2023-01-01 03:00:00')
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);

it('will fail if date format is not valid', function () {
    $this->model->postponer()
        ->startFrom('2022-15-20 00:00:00')
        ->update(['name' => 'Stefan']);
})->throws(InvalidFormatException::class);

it('will fail if date is in the past', function () {
    $this->model->postponer()
        ->startFrom('2022-12-31 23:59:59')
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);

it('will fail if there is combination of start at and delay for', function () {
    $this->model->postponer()
        ->startFrom('2023-12-31 23:59:59')
        ->delayForMinutes(10)
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);

it('will fail if there is combination of revert at and keep for', function () {
    $this->model->postponer()
        ->revertAt('2023-12-31 23:59:59')
        ->keepForMinutes(10)
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);

it('will fail if keep for method is used twice', function () {
    $this->model->postponer()
        ->keepForMinutes(10)
        ->keepForDays(1)
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);

it('will fail if delay for method is used twice', function () {
    $this->model->postponer()
        ->delayForMinutes(10)
        ->delayForDays(1)
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);

it('will fail if start from method is used twice', function () {
    $this->model->postponer()
        ->startFrom("2023-12-31 23:59:59")
        ->startFrom("2023-11-31 23:59:59")
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);

it('will fail if revert at method is used twice', function () {
    $this->model->postponer()
        ->revertAt("2023-12-31 23:59:59")
        ->revertAt("2023-11-31 23:59:59")
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);
