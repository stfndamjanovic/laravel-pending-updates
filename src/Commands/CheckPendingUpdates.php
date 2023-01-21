<?php

namespace Stfn\PendingUpdates\Commands;

use Illuminate\Console\Command;

class CheckPendingUpdates extends Command
{
    public $signature = 'pending-updates:check';

    public $description = 'Check pending updates and revert to the original tables';

    public function handle(): int
    {
        $model = config('pending-updates.model');

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
