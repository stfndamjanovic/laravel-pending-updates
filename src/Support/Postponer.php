<?php

namespace Stfn\PendingUpdates\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Stfn\PendingUpdates\Exceptions\InvalidAttributeException;
use Stfn\PendingUpdates\Exceptions\InvalidPendingParametersException;

class Postponer
{
    const DATE_FORMAT = 'Y-m-d H:i:s';

    protected $model;

    protected $delayFor;

    protected $keepFor;

    protected $startAt;

    protected $revertAt;

    /**
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @param int $minutes
     * @return $this
     * @throws InvalidPendingParametersException
     */
    public function keepForMinutes(int $minutes)
    {
        return $this->setKeepForProperty($this->minutesToSeconds($minutes));
    }

    /**
     * @param int $hours
     * @return $this
     * @throws InvalidPendingParametersException
     */
    public function keepForHours(int $hours)
    {
        return $this->setKeepForProperty($this->hoursToSeconds($hours));
    }

    /**
     * @param int $days
     * @return $this
     * @throws InvalidPendingParametersException
     */
    public function keepForDays(int $days)
    {
        return $this->setKeepForProperty($this->daysToSeconds($days));
    }

    /**
     * @param int $minutes
     * @return $this
     * @throws InvalidPendingParametersException
     */
    public function delayForMinutes(int $minutes)
    {
        return $this->setDelayForProperty($this->minutesToSeconds($minutes));
    }

    /**
     * @param int $hours
     * @return $this
     * @throws InvalidPendingParametersException
     */
    public function delayForHours(int $hours)
    {
        return $this->setDelayForProperty($this->hoursToSeconds($hours));
    }

    /**
     * @param int $days
     * @return $this
     * @throws InvalidPendingParametersException
     */
    public function delayForDays(int $days)
    {
        return $this->setDelayForProperty($this->daysToSeconds($days));
    }

    /**
     * @param string $timestamp
     * @return $this
     * @throws InvalidPendingParametersException
     */
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

    /**
     * @param string $timestamp
     * @return $this
     * @throws InvalidPendingParametersException
     */
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

    /**
     * @param Carbon $timestamp
     * @return void
     * @throws InvalidPendingParametersException
     */
    protected function validateTimestamp(Carbon $timestamp)
    {
        if ($timestamp->isPast()) {
            throw InvalidPendingParametersException::pastTimestamp();
        }
    }

    /**
     * @param int $seconds
     * @return $this
     * @throws InvalidPendingParametersException
     */
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

    /**
     * @param int $seconds
     * @return $this
     * @throws InvalidPendingParametersException
     */
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

    /**
     * @param int $minutes
     * @return float|int
     */
    protected function minutesToSeconds(int $minutes)
    {
        return $minutes * 60;
    }

    /**
     * @param int $hours
     * @return float|int
     */
    protected function hoursToSeconds(int $hours)
    {
        return $hours * 60 * 60;
    }

    /**
     * @param int $days
     * @return float|int
     */
    protected function daysToSeconds(int $days)
    {
        return $days * 60 * 60 * 24;
    }

    /**
     * @return array
     * @throws InvalidPendingParametersException
     */
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

        if (Carbon::parse($revertAt)->diffInDays(now()) > config('pending-updates.max_postpone_days')) {
            throw InvalidPendingParametersException::aboveMaximumPostponeDays();
        }

        return [$startAt, $revertAt];
    }

    /**
     * @param array $attributes
     * @param array $options
     * @return bool
     * @throws InvalidAttributeException
     * @throws InvalidPendingParametersException
     */
    public function update(array $attributes = [], array $options = [])
    {
        $this->validatePendingAttributes($attributes);

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

        $this->model->pendingUpdates()->create([
            'values' => $pendingAttributes,
            'start_at' => $startAt,
            'revert_at' => $revertAt,
        ]);

        return true;
    }

    /**
     * @param $attributes
     * @return void
     * @throws InvalidAttributeException
     */
    protected function validatePendingAttributes($attributes)
    {
        $disallowedAttributes = array_diff(
            array_keys($attributes),
            $this->model->allowedPendingAttributes()
        );

        if (! empty($disallowedAttributes)) {
            throw InvalidAttributeException::create();
        }
    }
}
