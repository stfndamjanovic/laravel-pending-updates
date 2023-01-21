<?php

namespace Stfn\PostponeUpdates\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Stfn\PostponeUpdates\Support\Postponer;

/** @mixin Model */
trait HasPostponedUpdates
{
    protected $postponer;

    protected $attributesToPostpone = [];

    protected $postponeStartAt;

    protected $postponeRevertAt;

    protected $postponeUpdateConfirmed = false;

    public static function bootHasPostponedUpdates(): void
    {
        static::updating(function (Model $model) {
            if (! $model->postponer instanceof Postponer) {
                return;
            }

            [$startAt, $revertAt] = $model->postponer->get();
            $model->postponeStartAt = $startAt;
            $model->postponeRevertAt = $revertAt;

            $dirty = $model->getDirty();

            if (! $startAt) {
                $model->attributesToPostpone = array_intersect_key($model->getOriginal(), $dirty);

                return;
            }

            $model->attributesToPostpone = $dirty;
            $model->discardChanges();
            $model->timestamps = false;

            $model->createPostponeUpdate();
        });

        static::updated(function (Model $model) {
            if (! $model->postponer instanceof Postponer) {
                return;
            }

            $model->postponeUpdateConfirmed = true;
            $model->createPostponeUpdate();
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

    protected function createPostponeUpdate()
    {
        // If postponed update already exists, remove that one and create another one
        if ($this->hasPendingUpdate()) {
            $this->postponedUpdate()->delete();
        }

        $this->postponedUpdate()->create([
            'values' => $this->attributesToPostpone,
            'start_at' => $this->postponeStartAt,
            'revert_at' => $this->postponeRevertAt,
            'is_confirmed' => $this->postponeUpdateConfirmed,
        ]);
    }
}
