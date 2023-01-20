<?php

namespace Stfn\PostponeUpdates\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;
use Stfn\PostponeUpdates\Exceptions\InvalidPostponeParametersException;

class Postponer
{
    use ForwardsCalls;

    protected $model;

    protected $startTime;

    protected $startTimeUnit;

    protected $endTime;

    protected $endTimeUnit;

    protected $startAt;

    protected $revertAt;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function keepForMinutes(int $minutes)
    {
        return $this->setEndAtProperty($minutes, 'minute');
    }

    public function keepForHours(int $hours)
    {
        return $this->setEndAtProperty($hours, 'hours');
    }

    public function keepForDays(int $days)
    {
        return $this->setEndAtProperty($days, 'days');
    }

    public function delayForMinutes(int $minites)
    {
        return $this->setStartAtProperty($minites, 'minute');
    }

    public function delayForHours(int $hours)
    {
        return $this->setStartAtProperty($hours, 'hours');
    }

    public function delayForDays(int $days)
    {
        return $this->setStartAtProperty($days, 'days');
    }

    public function startFrom(string $timestamp)
    {
        $date = Carbon::parse($timestamp);

        $this->validateTimestamp($date);

        $this->startAt = $date->format('Y-m-d H:i:s');

        return $this;
    }

    public function revertAt(string $timestamp)
    {
        $date = Carbon::parse($timestamp);

        $this->validateTimestamp($date);

        $this->revertAt = $date->format('Y-m-d H:i:s');

        return $this;
    }

    protected function validateTimestamp(Carbon $timestamp)
    {
        if ($timestamp->isPast()) {
            throw InvalidPostponeParametersException::create();
        }
    }

    protected function setStartAtProperty($amount, $unit)
    {
        $this->startTime = $amount;

        $this->startTimeUnit = $unit;

        return $this;
    }

    protected function setEndAtProperty($amount, $unit)
    {
        $this->endTime = $amount;

        $this->endTimeUnit = $unit;

        return $this;
    }

    public function get()
    {
        $startAt = null;
        $revertAt = null;

        if ($this->startAt) {
            $startAt = $this->startAt;
        }

        if ($this->revertAt) {
            $revertAt = $this->revertAt;
        }

        if ($this->startTime) {
            $startAt = Carbon::now()->add($this->startTimeUnit, $this->startTime);
        }

        if ($this->endTime) {
            if ($startAt) {
                $revertAt = Carbon::parse($startAt)->add($this->endTimeUnit, $this->endTime);
            } else {
                $revertAt = Carbon::now()->add($this->endTimeUnit, $this->endTime);
            }
        }

        if (($startAt && $revertAt) && $startAt >= $revertAt) {
            throw InvalidPostponeParametersException::create();
        }

        if (! $startAt && ! $revertAt) {
            throw InvalidPostponeParametersException::create();
        }

        return [$startAt, $revertAt];
    }

    public function __call($method, $parameters)
    {
        if ($method != 'update') {
            static::throwBadMethodCallException($method);
        }

        return $this->forwardCallTo($this->model, $method, $parameters);
    }
}
