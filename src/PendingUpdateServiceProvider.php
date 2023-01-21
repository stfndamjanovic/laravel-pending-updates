<?php

namespace Stfn\PendingUpdates;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Stfn\PendingUpdates\Commands\CheckPendingUpdates;
use Stfn\PendingUpdates\Exceptions\InvalidPendingUpdateModel;
use Stfn\PendingUpdates\Models\PendingUpdate;

class PendingUpdateServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-pending-updates')
            ->hasConfigFile('pending-updates')
            ->hasMigration('create_pending_updates_table')
            ->hasCommand(CheckPendingUpdates::class);
    }

    public function packageBooted()
    {
        $model = config('pending-updates.model');

        if (! is_a($model, PendingUpdate::class, true)) {
            throw InvalidPendingUpdateModel::create($model);
        }
    }
}
