<?php

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Relations\Relation;
use function Spatie\PestPluginTestTime\testTime;
use Stfn\PostponeUpdates\Exceptions\InvalidPostponeParametersException;
use Stfn\PostponeUpdates\Tests\Support\Models\TestModel;

beforeEach(function () {
    testTime()->freeze('2023-01-01 00:00:00');

    Relation::morphMap(['TestModel' => TestModel::class]);

    $this->attributes = ['name' => 'John Doe', 'secret' => 'hash'];

    $this->model = TestModel::factory()->create($this->attributes);
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
        ->startFrom('2023-12-31 23:59:59')
        ->startFrom('2023-11-31 23:59:59')
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);

it('will fail if revert at method is used twice', function () {
    $this->model->postponer()
        ->revertAt('2023-12-31 23:59:59')
        ->revertAt('2023-11-31 23:59:59')
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);

it('will fail if delay for minutes is below 1', function () {
    $this->model->postponer()
        ->delayForMinutes(-1)
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);

it('will fail if keep for minutes is below 1', function () {
    $this->model->postponer()
        ->keepForMinutes(-1)
        ->update(['name' => 'Stefan']);
})->throws(InvalidPostponeParametersException::class);
