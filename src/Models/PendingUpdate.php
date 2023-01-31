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
    /**
     * @var array $guarded
     */
    public array $guarded = [];

    /**
     * @var string[] $casts
     */
    public array $casts = [
        'values' => 'array',
    ];

    public string $table = 'pending_updates';

    /**
     * @return mixed
     */
    public function parent()
    {
        return $this->morphTo();
    }

    /**
     * @return void
     */
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

    /**
     * @return void
     */
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

    /**
     * @return mixed
     */
    public function getParentAttributes()
    {
        return $this->parent->getAttributes();
    }

    /**
     * @return mixed
     */
    protected function revertParentModel()
    {
        return $this->parent->forceFill($this->values)->save();
    }

    /**
     * @return mixed
     */
    public function shouldRevert()
    {
        return Carbon::now()->gt($this->revert_at);
    }

    /**
     * @return mixed
     */
    public function shouldApply()
    {
        return Carbon::now()->gt($this->start_at);
    }

    /**
     * @param $exception
     * @param $model
     * @return void
     */
    public function updateCannotBeApplied($exception, $model)
    {
        report($exception);

        $this->delete();
    }
}
