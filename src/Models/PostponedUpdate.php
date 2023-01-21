<?php

namespace Stfn\PostponeUpdates\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PostponedUpdate extends Model
{
    public $guarded = [];

    public $casts = [
        'values' => 'array',
    ];

    public $table = 'postponed_updates';

    public function parent()
    {
        return $this->morphTo();
    }

    public function revert()
    {
        if (! $this->parent) {
            $this->delete();

            return;
        }

        $this->revertParentModel();

        $this->delete();
    }

    public function apply()
    {
        if (! $this->parent) {
            $this->delete();

            return;
        }

        $parentAttributes = array_intersect_key($this->getParentAttributes(), $this->values);

        $this->revertParentModel();

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

    public function revertParentModel()
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
}
