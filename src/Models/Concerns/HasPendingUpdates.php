<?php

namespace Stfn\PendingUpdates\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Stfn\PendingUpdates\Support\Postponer;

/** @mixin Model */
trait HasPendingUpdates
{
    public static function bootHasPendingUpdates(): void
    {
        static::deleted(function (Model $model) {
            $model->pendingUpdates()->delete();
        });
    }

    public function pendingUpdates()
    {
        return $this->morphMany($this->getPendingUpdateModelClass(), 'parent');
    }

    public function postpone()
    {
        return new Postponer($this);
    }

    protected function getPendingUpdateModelClass()
    {
        return config('pending-updates.model');
    }

    public function hasPendingUpdates()
    {
        return $this->pendingUpdates()->exists();
    }

    public function allowedPendingAttributes()
    {
        return $this->getFillable();
    }
}
