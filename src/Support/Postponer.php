<?php

namespace Stfn\PendingUpdates\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Stfn\PendingUpdates\Exceptions\InvalidPendingParametersException;

class Postponer
{
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
            throw InvalidPendingParametersException::twicePropertySet();
        }

        $this->startAt = $date->format(self::DATE_FORMAT);

        return $this;
    }

    public function revertAt(string $timestamp)
    {
        $date = Carbon::parse($timestamp);

        $this->validateTimestamp($date);

        if ($this->revertAt) {
            throw InvalidPendingParametersException::twicePropertySet();
        }

        $this->revertAt = $date->format(self::DATE_FORMAT);

        return $this;
    }

    protected function validateTimestamp(Carbon $timestamp)
    {
        if ($timestamp->isPast()) {
            throw InvalidPendingParametersException::pastTimestamp();
        }
    }

    protected function setDelayForProperty(int $seconds)
    {
        if ($this->delayFor) {
            throw InvalidPendingParametersException::twicePropertySet();
        }

        if ($seconds <= 0) {
            throw InvalidPendingParametersException::negativeTimeConfiguration();
        }

        $this->delayFor = $seconds;

        return $this;
    }

    protected function setKeepForProperty(int $seconds)
    {
        if ($this->keepFor) {
            throw InvalidPendingParametersException::twicePropertySet();
        }

        if ($seconds <= 0) {
            throw InvalidPendingParametersException::negativeTimeConfiguration();
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

    protected function get()
    {
        if ($this->startAt && $this->delayFor) {
            throw InvalidPendingParametersException::invalidStartAtConfiguration();
        }

        if ($this->revertAt && $this->keepFor) {
            throw InvalidPendingParametersException::invalidRevertAtConfiguration();
        }

        $startAt = $this->startAt;
        $revertAt = $this->revertAt;

        if ($this->delayFor) {
            $startAt = Carbon::now()
                ->addSeconds($this->delayFor)
                ->format(self::DATE_FORMAT);
        }

        if ($this->keepFor) {
            $revertAt = Carbon::parse($startAt)
                ->addSeconds($this->keepFor)
                ->format(self::DATE_FORMAT);
        }

        if (($startAt && $revertAt) && $startAt >= $revertAt) {
            throw InvalidPendingParametersException::invalidCombinationOfStartAndRevertAt();
        }

        if (! $startAt && ! $revertAt) {
            throw InvalidPendingParametersException::invalidTimestampConfiguration();
        }

        return [$startAt, $revertAt];
    }

    public function update(array $attributes = [], array $options = [])
    {
        [$startAt, $revertAt] = $this->get();

        $this->model->fill($attributes);

        $pendingAttributes = $this->model->getDirty();

        if (! $startAt) {
            $pendingAttributes = array_intersect_key($this->model->getOriginal(), $pendingAttributes);
            $this->model->update($attributes, $options);
        }

        if (! $pendingAttributes) {
            return false;
        }

        // If pending update already exists, remove that one and create another one
        if ($this->model->hasPendingUpdate()) {
            $this->model->pendingUpdate()->delete();
        }

        $this->model->pendingUpdate()->create([
            'values' => $pendingAttributes,
            'start_at' => $startAt,
            'revert_at' => $revertAt,
        ]);

        return true;
    }
}
