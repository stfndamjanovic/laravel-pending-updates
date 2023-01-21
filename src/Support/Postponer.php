<?php

namespace Stfn\PostponeUpdates\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;
use Stfn\PostponeUpdates\Exceptions\InvalidPostponeParametersException;

class Postponer
{
    use ForwardsCalls;

    const DATE_FORMAT = 'Y-m-d H:i:s';

    protected $model;

    protected $delayFor;

    protected $keepFor;

    protected $startAt;

    protected $revertAt;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function keepForMinutes(int $minutes)
    {
        return $this->setKeepForProperty($this->minutesToSeconds($minutes));
    }

    public function keepForHours(int $hours)
    {
        return $this->setKeepForProperty($this->hoursToSeconds($hours));
    }

    public function keepForDays(int $days)
    {
        return $this->setKeepForProperty($this->daysToSeconds($days));
    }

    public function delayForMinutes(int $minutes)
    {
        return $this->setDelayForProperty($this->minutesToSeconds($minutes));
    }

    public function delayForHours(int $hours)
    {
        return $this->setDelayForProperty($this->hoursToSeconds($hours));
    }

    public function delayForDays(int $days)
    {
        return $this->setDelayForProperty($this->daysToSeconds($days));
    }

    public function startFrom(string $timestamp)
    {
        $date = Carbon::parse($timestamp);

        $this->validateTimestamp($date);

        if ($this->startAt) {
            throw InvalidPostponeParametersException::create();
        }

        $this->startAt = $date->format(self::DATE_FORMAT);

        return $this;
    }

    public function revertAt(string $timestamp)
    {
        $date = Carbon::parse($timestamp);

        $this->validateTimestamp($date);

        if ($this->revertAt) {
            throw InvalidPostponeParametersException::create();
        }

        $this->revertAt = $date->format(self::DATE_FORMAT);

        return $this;
    }

    protected function validateTimestamp(Carbon $timestamp)
    {
        if ($timestamp->isPast()) {
            throw InvalidPostponeParametersException::create();
        }
    }

    protected function setDelayForProperty(int $seconds)
    {
        if ($this->delayFor) {
            throw InvalidPostponeParametersException::create();
        }

        if ($seconds <= 0) {
            throw InvalidPostponeParametersException::create();
        }

        $this->delayFor = $seconds;

        return $this;
    }

    protected function setKeepForProperty(int $seconds)
    {
        if ($this->keepFor) {
            throw InvalidPostponeParametersException::create();
        }

        if ($seconds <= 0) {
            throw InvalidPostponeParametersException::create();
        }

        $this->keepFor = $seconds;

        return $this;
    }

    protected function minutesToSeconds(int $minutes)
    {
        return $minutes * 60;
    }

    protected function hoursToSeconds(int $hours)
    {
        return $hours * 60 * 60;
    }

    protected function daysToSeconds(int $days)
    {
        return $days * 60 * 60 * 24;
    }

    public function get()
    {
        if ($this->startAt && $this->delayFor) {
            throw InvalidPostponeParametersException::create();
        }

        if ($this->revertAt && $this->keepFor) {
            throw InvalidPostponeParametersException::create();
        }

        $startAt = $this->startAt;
        $revertAt = $this->revertAt;

        if ($this->delayFor) {
            $startAt = Carbon::now()->addSeconds($this->delayFor);
        }

        if ($this->keepFor) {
            if ($startAt) {
                $revertAt = Carbon::parse($startAt)->addSeconds($this->keepFor);
            } else {
                $revertAt = Carbon::now()->addSeconds($this->keepFor);
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
