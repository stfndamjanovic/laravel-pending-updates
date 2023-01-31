<?php

namespace Stfn\PendingUpdates\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Stfn\PendingUpdates\Support\Postponer;

/** @mixin Model */
trait HasPendingUpdates
{
    /**
     * @return void
     */
    public static function bootHasPendingUpdates(): void
    {
        static::deleted(function (Model $model) {
            $model->pendingUpdates()->delete();
        });
    }

    /**
     * @return mixed
     */
    public function pendingUpdates()
    {
        return $this->morphMany($this->getPendingUpdateModelClass(), 'parent');
    }

    /**
     * @return Postponer
     */
    public function postpone()
    {
        return new Postponer($this);
    }

    /**
     * @return mixed
     */
    protected function getPendingUpdateModelClass()
    {
        return config('pending-updates.model');
    }

    /**
     * @return mixed
     */
    public function hasPendingUpdates()
    {
        return $this->pendingUpdates()->exists();
    }

    /**
     * @return mixed
     */
    public function allowedPendingAttributes()
    {
        return $this->getFillable();
    }
}
