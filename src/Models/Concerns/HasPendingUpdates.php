<?php

namespace Stfn\PendingUpdates\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Stfn\PendingUpdates\Support\PendingBuilder;

/** @mixin Model */
trait HasPendingUpdates
{
    protected $pendingBuilder;

    protected $pendingAttributes = [];

    protected $pendingStartAt;

    protected $pendingRevertAt;

    protected $pendingUpdateConfirmed = false;

    public static function bootHasPendingUpdates(): void
    {
        static::updating(function (Model $model) {
            if (! $model->pendingBuilder instanceof PendingBuilder) {
                return;
            }

            [$startAt, $revertAt] = $model->pendingBuilder->get();
            $model->pendingStartAt = $startAt;
            $model->pendingRevertAt = $revertAt;

            $dirty = $model->getDirty();

            if (! $startAt) {
                $model->pendingAttributes = array_intersect_key($model->getOriginal(), $dirty);

                return;
            }

            $model->pendingAttributes = $dirty;
            $model->discardChanges();
            $model->timestamps = false;

            $model->createPendingUpdate();
        });

        static::updated(function (Model $model) {
            if (! $model->pendingBuilder instanceof PendingBuilder) {
                return;
            }

            $model->pendingUpdateConfirmed = true;
            $model->createPendingUpdate();
        });

        static::deleted(function (Model $model) {
            $model->pendingUpdate()->delete();
        });
    }

    public function pendingUpdate()
    {
        return $this->morphOne($this->getPendingUpdateModelClassName(), 'parent');
    }

    public function pending()
    {
        return $this->pendingBuilder = new PendingBuilder($this);
    }

    protected function getPendingUpdateModelClassName()
    {
        return config('pending-updates.model');
    }

    public function hasPendingUpdate()
    {
        return $this->pendingUpdate()->exists();
    }

    protected function createPendingUpdate()
    {
        // If pending update already exists, remove that one and create another one
        if ($this->hasPendingUpdate()) {
            $this->pendingUpdate()->delete();
        }

        $this->pendingUpdate()->create([
            'values' => $this->pendingAttributes,
            'start_at' => $this->pendingStartAt,
            'revert_at' => $this->pendingRevertAt,
            'is_confirmed' => $this->pendingUpdateConfirmed,
        ]);
    }
}
