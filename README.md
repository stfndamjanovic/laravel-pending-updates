# Postpone eloquent updates or temporary keep model updated for some time 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/stfndamjanovic/laravel-update-postponer.svg?style=flat-square)](https://packagist.org/packages/stfndamjanovic/laravel-temp-actions)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/stfndamjanovic/laravel-update-postponer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/stfndamjanovic/laravel-temp-actions/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/stfndamjanovic/laravel-update-postponer/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/stfndamjanovic/laravel-temp-actions/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/stfndamjanovic/laravel-update-postponer.svg?style=flat-square)](https://packagist.org/packages/stfndamjanovic/laravel-temp-actions)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

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

## Usage

```php
$news = News::find(5);

$news->pending()
    ->startFrom('2023-01-01 00:00:00')
    ->keepForHours(24)
    ->update(['is_active' => true]);
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
