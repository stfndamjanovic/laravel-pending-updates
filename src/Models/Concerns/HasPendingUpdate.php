<?php

namespace Stfn\PendingUpdates\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Stfn\PendingUpdates\Support\Postponer;

/** @mixin Model */
trait HasPendingUpdate
{
    public static function bootHasPendingUpdate(): void
    {
        static::deleted(function (Model $model) {
            $model->pendingUpdate()->delete();
        });
    }

    public function pendingUpdate()
    {
        return $this->morphOne($this->getPendingUpdateModelClass(), 'parent');
    }

    public function postpone()
    {
        return new Postponer($this);
    }

    protected function getPendingUpdateModelClass()
    {
        return config('pending-updates.model');
    }

    public function hasPendingUpdate()
    {
        return $this->pendingUpdate()->exists();
    }

    public function allowedPendingAttributes()
    {
        return $this->getFillable();
    }
}
