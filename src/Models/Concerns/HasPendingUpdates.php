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
            $model->pendingUpdate()->delete();
        });
    }

    public function pendingUpdate()
    {
        return $this->morphOne($this->getPendingUpdateModelClassName(), 'parent');
    }

    public function postpone()
    {
        return new Postponer($this);
    }

    protected function getPendingUpdateModelClassName()
    {
        return config('pending-updates.model');
    }

    public function hasPendingUpdate()
    {
        return $this->pendingUpdate()->exists();
    }
}
