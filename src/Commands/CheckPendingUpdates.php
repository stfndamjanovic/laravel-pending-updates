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

        $model::where('start_at', '<=', now())
            ->orWhere('revert_at', '<=', now())
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
