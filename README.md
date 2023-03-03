# Postpone model updates or temporarily keep them updated for some time

[![Latest Version on Packagist](https://img.shields.io/packagist/v/stfn/laravel-pending-updates.svg?style=flat-square)](https://packagist.org/packages/stfn/laravel-pending-updates)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/stfndamjanovic/laravel-pending-updates/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/stfndamjanovic/laravel-pending-updates/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/stfndamjanovic/laravel-pending-updates/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/stfndamjanovic/laravel-pending-updates/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)

When updating an Eloquent model, by using this package, you can postpone updating process for some time.

```php
$news = News::find(1);

$news->postpone()
    ->startFrom('2023-01-01 00:00:00')
    ->keepForHours(24)
    ->update(['is_active' => true]);
```
The model itself will not be updated in this case. The package will just schedule an update for you.
So this news will be active only on 1st January for the whole day and after that, the package will revert the news to its previous state.

## Installation

You can install the package via composer:

```bash
composer require stfn/laravel-pending-updates
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="pending-updates-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="pending-updates-config"
```

This is the contents of the published config file:

```php
return [
    // Maximum postpone in days.
    'max_postpone_days' => 10,

    // The model uses to store pending updates.
    'model' => \Stfn\PendingUpdates\Models\PendingUpdate::class,
];

```
When running the console command `pending-updates:check` all pending updates will be checked
and if there is a need to revert some update to original table, this command will do that for you.

That command needs to be scheduled in the Laravel console kernel.
```php
// app/Console/Kernel.php
use Stfn\PendingUpdates\Commands\CheckPendingUpdates;

protected function schedule(Schedule $schedule)
{
   $schedule->command(CheckPendingUpdates::class)->everyMinute();
}
```

## Usage
You should add the HasPendingUpdates trait to all models which need to have a pending update option.

```php
use Illuminate\Database\Eloquent\Model;
use Stfn\PendingUpdates\Models\Concerns\HasPendingUpdates;

class Ticket extends Model
{
    use HasPendingUpdates;
}
```

With this in place, you will be able to postpone update of this model.

### Using keep for

By using keep for, update will be performed at the moment, but package will revert changes after specific number of minutes, hours or days.

```php
$ticket = Ticket::find(1);

// Update ticket price to 200 and keep it updated for 60 minutes.
$ticket->postpone()
    ->keepForMinutes(60)
    ->update(['price' => 200]);
    
// Update ticket price to 200 and keep it updated for 12 hours.
$ticket->postpone()
    ->keepForHours(12)
    ->update(['price' => 200]);

// Update ticket price to 200 and keep it updated for 3 days.
$ticket->postpone()
    ->keepForDays(3)
    ->update(['price' => 200]);
```

### Using delay for

By using delay for, update will be performed later, after specific number of minutes, hours or days.

```php
$ticket = Ticket::find(1);

// Update ticket price to 200 after 60 minutes from now and keep it like that for unlimited time.
$ticket->postpone()
    ->delayForMinutes(60)
    ->update(['price' => 200]);

// Update ticket price to 200 after 12 hours from now and keep it like that for unlimited time.
$ticket->postpone()
    ->delayForHours(12)
    ->update(['price' => 200]);

// Update ticket price to 200 after 3 days from now and keep it like that for unlimited time.
$ticket->postpone()
    ->delayForDays(3)
    ->update(['price' => 200]);
```

### Using timestamps

You can also use timestamps to specify exact time when you want to update some model.

```php
$product = Product::find(1);

// Update product to be unavailable from 1st January.
$product->postpone()
    ->startFrom("2023-01-01 00:00:00")
    ->update(['is_available' => false]);

// Update product to be unavailable until 4th January.
$product->postpone()
    ->revertAt("2023-04-01 00:00:00")
    ->update(['is_available' => false]);

// Update product to be unavailable from 1st January to 4th January.
$product->postpone()
    ->startFrom("2023-01-01 00:00:00")
    ->revertAt("2023-04-01 00:00:00")
    ->update(['is_available' => false]);
```

### Using combination

A combination of specific minutes, hours, or days with timestamps is also possible.
```php
$product = Product::find(1);

// Update product to be unavailable from 1st January and keep that state for 1 day.
$product->postpone()
    ->startFrom("2023-01-01 00:00:00")
    ->keepForDays(1)
    ->update(['is_available' => false]);

// Update product to became unavailable after 60 minutes from now and keep that state until 4th January.
$product->postpone()
    ->delayForMinutes(60)
    ->revertAt("2023-04-01 00:00:00")
    ->update(['price' => 200]);
```

### Using methods on the model

By default, all fillable attributes are allowed to be postponed, but you can change that by overriding
`allowedPendingAttributes` method.

```php
use Illuminate\Database\Eloquent\Model;
use Stfn\PendingUpdates\Models\Concerns\HasPendingUpdates;

class Ticket extends Model
{
    use HasPendingUpdates;
    
    public function allowedPendingAttributes()
    {
        return ['price'];
    }
}
```
Keep in mind that those fields also need to be fillable.

Sometimes scheduled update may fail, for various reasons.
By default, the package will send that exception to your configured external
report service like Sentry, Flare, or Bugsnag and after that,
the record from the `pending_updates` table will be removed. If you want to change this behavior, you can
override the `updateCannotBeApplied` method on the `PendingUpdateModel`.

```php
use Illuminate\Database\Eloquent\Model;
use Stfn\PendingUpdates\Models\PendingUpdate;

class CustomPendingUpdate extends PendingUpdate
{    
    public function updateCannotBeApplied($exception, $model)
    {
        // Your custom logic here
    }
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Stefan Damjanovic](https://github.com/stfndamjanovic)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
