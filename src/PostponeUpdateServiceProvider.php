<?php

namespace Stfn\PostponeUpdates;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Stfn\PostponeUpdates\Commands\CheckPostponedUpdates;
use Stfn\PostponeUpdates\Exceptions\InvalidPostponedUpdateModel;
use Stfn\PostponeUpdates\Models\PostponedUpdate;

class PostponeUpdateServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('postponed-eloquent-actions')
            ->hasConfigFile('postpone-updates')
            ->hasMigration('create_postponed_updates_table')
            ->hasCommand(CheckPostponedUpdates::class);
    }

    public function packageBooted()
    {
        $model = config('postpone-updates.model');

        if (! is_a($model, PostponedUpdate::class, true)) {
            throw InvalidPostponedUpdateModel::create($model);
        }
    }
}
