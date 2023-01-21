# Postpone model updates or temporary keep them updated for some time 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/stfndamjanovic/laravel-update-postponer.svg?style=flat-square)](https://packagist.org/packages/stfndamjanovic/laravel-temp-actions)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/stfndamjanovic/laravel-update-postponer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/stfndamjanovic/laravel-temp-actions/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/stfndamjanovic/laravel-update-postponer/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/stfndamjanovic/laravel-temp-actions/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/stfndamjanovic/laravel-update-postponer.svg?style=flat-square)](https://packagist.org/packages/stfndamjanovic/laravel-temp-actions)

When updating an Eloquent model, by using this package, you can postpone updating process for some time.

```php
$news = News::find(1); // an Eloquent model

$news->pending()
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
php artisan vendor:publish --tag="laravel-pending-updates-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-pending-updates-config"
```

This is the contents of the published config file:

```php
return [
    'model' => \Stfn\PendingUpdates\Models\PendingUpdate::class,
];
```
When running the console command `pending-updates:check` all pending updates will be checked
and if there is a need to revert some update to original table, this command will do that for you.

This command need to be scheduled in Laravel's console kernel.
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
$ticket = Ticket::find(1); // an Eloquent model

// Update ticket price to 200 and keep it updated for 60 minutes.
$ticket->pending()
    ->keepForMinutes(60)
    ->update(['price' => 200]);
    
// Update ticket price to 200 and keep it updated for 12 hours.
$ticket->pending()
    ->keepForHours(12)
    ->update(['price' => 200]);

// Update ticket price to 200 and keep it updated for 3 days.
$ticket->pending()
    ->keepForDays(3)
    ->update(['price' => 200]);
```

### Using delay for

By using delay for, update will be performed later, after specific number of minutes, hours or days.

```php
$ticket = Ticket::find(1); // an Eloquent model

// Update ticket price to 200 after 60 minutes from now and keep it like that for unlimited time.
$ticket->pending()
    ->dalayForMinutes(60)
    ->update(['price' => 200]);

// Update ticket price to 200 after 12 hours from now and keep it like that for unlimited time.
$ticket->pending()
    ->dalayForHours(12)
    ->update(['price' => 200]);

// Update ticket price to 200 after 3 days from now and keep it like that for unlimited time.
$ticket->pending()
    ->dalayForDays(3)
    ->update(['price' => 200]);
```

### Using timestamps

You can also use timestamps to specify exact time when you want to update some model.

```php
$ticket = Ticket::find(1); // an Eloquent model

// Update ticket price to 200 at 1st January and keep it updated for unlimited time.
$ticket->pending()
    ->startFrom("2023-01-01 00:00:00")
    ->update(['price' => 200]);

// Update ticket price to 200 at the moment and revert to previous value at 4th January.
$ticket->pending()
    ->revertAt("2023-04-01 00:00:00")
    ->update(['price' => 200]);

// Update ticket price to 200 at 1st January and revert to previous value at 4th January.
$ticket->pending()
    ->startFrom("2023-01-01 00:00:00")
    ->revertAt("2023-04-01 00:00:00")
    ->update(['price' => 200]);
```

### Using combination

A combination of specific minutes, hours, or days with timestamps is also possible.
```php
$ticket = Ticket::find(1); // an Eloquent model

// Update ticket price to 200 from 1st January and keep it updated for 1 day.
$ticket->pending()
    ->startFrom("2023-01-01 00:00:00")
    ->keepForDays(1)
    ->update(['price' => 200]);

// Update ticket price to 200 after 60 minutes from now and keep it updated until 4th January.
$ticket->pending()
    ->delayForMinutes(60)
    ->revertAt("2023-04-01 00:00:00")
    ->update(['price' => 200]);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Stefan Damjanovic](https://github.com/stfndamjanovic)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
