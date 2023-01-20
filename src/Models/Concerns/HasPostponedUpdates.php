<?php

namespace Stfn\PostponeUpdates\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Stfn\PostponeUpdates\Support\Postponer;

/** @mixin Model */
trait HasPostponedUpdates
{
    protected $postponer;

    public static function bootHasPostponedUpdates(): void
    {
        static::updating(function (Model $model) {
            if (! $model->postponer instanceof Postponer) {
                return;
            }

            [$startAt, $revertAt] = $model->postponer->get();

            $attributesToPostpone = $model->getDirty();

            if ($startAt) {
                $model->discardChanges();
            } else {
                $attributesToPostpone = array_intersect_key($model->getOriginal(), $attributesToPostpone);
            }

            // If postponed update already exists, remove that one and create another one
            if ($model->hasPendingUpdate()) {
                $model->postponedUpdate()->delete();
            }

            $model->postponedUpdate()->create([
                'values' => $attributesToPostpone,
                'start_at' => $startAt,
                'revert_at' => $revertAt,
            ]);

            $model->postponer = null;

            // To avoid touch of updated_at value
            $model->timestamps = false;
        });

        static::deleted(function (Model $model) {
            $model->postponedUpdate()->delete();
        });
    }

    public function postponedUpdate()
    {
        return $this->morphOne($this->getPostponedUpdateModelClassName(), 'parent');
    }

    public function postponer()
    {
        return $this->postponer = new Postponer($this);
    }

    protected function getPostponedUpdateModelClassName()
    {
        return config('postpone-updates.model');
    }

    public function hasPendingUpdate()
    {
        return $this->postponedUpdate()->exists();
    }
}
