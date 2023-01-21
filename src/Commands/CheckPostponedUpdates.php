<?php

namespace Stfn\PostponeUpdates\Commands;

use Illuminate\Console\Command;

class CheckPostponedUpdates extends Command
{
    public $signature = 'postponed-updates:check';

    public $description = 'This command will find and perform updates on all scheduled delay updates';

    public function handle(): int
    {
        $model = config('postpone-updates.model');

        $model::where('is_confirmed', false)
            ->where('created_at', '<=', now()->subMinutes(5))
            ->delete();

        $model::where(function ($query) {
            $query->where('start_at', '<=', now())
                ->orWhere('revert_at', '<=', now());
        })->where('is_confirmed', true)
            ->get()
            ->each(function ($action) {
                if ($action->shouldRevert()) {
                    $action->revert();
                }

                if ($action->shouldApply()) {
                    $action->apply();
                }
            });

        return self::SUCCESS;
    }
}
