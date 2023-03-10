<?php

namespace Stfn\PendingUpdates\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $start_at
 * @property string $revert_at
 * @property array $values
 * @property Model $parent
 */
class PendingUpdate extends Model
{
    public $guarded = [];

    public $casts = [
        'values' => 'array',
    ];

    public $table = 'pending_updates';

    public function parent()
    {
        return $this->morphTo();
    }

    public function revert()
    {
        if (! $this->parent instanceof Model) {
            $this->delete();

            return;
        }

        try {
            $this->revertParentModel();
        } catch (\Exception $exception) {
            $this->updateCannotBeApplied($exception, $this->parent);

            return;
        }

        $this->delete();
    }

    public function apply()
    {
        if (! $this->parent instanceof Model) {
            $this->delete();

            return;
        }

        $parentAttributes = array_intersect_key($this->getParentAttributes(), $this->values);

        try {
            $this->revertParentModel();
        } catch (\Exception $exception) {
            $this->updateCannotBeApplied($exception, $this->parent);

            return;
        }

        if (! $this->revert_at) {
            $this->delete();

            return;
        }

        $this->update(['start_at' => null, 'values' => $parentAttributes]);
    }

    public function getParentAttributes()
    {
        return $this->parent->getAttributes();
    }

    protected function revertParentModel()
    {
        return $this->parent->forceFill($this->values)->save();
    }

    public function shouldRevert()
    {
        return Carbon::now()->gt($this->revert_at);
    }

    public function shouldApply()
    {
        return Carbon::now()->gt($this->start_at);
    }

    public function updateCannotBeApplied($exception, $model)
    {
        report($exception);

        $this->delete();
    }
}
