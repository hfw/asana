## Laravel Facade

![](https://img.shields.io/badge/laravel-11-darkred)

The files here provide an Asana API facade to Laravel.

`hfw/asana` does not require Laravel; the facade is here as a convenience if you happen to use Laravel.

### Installation

```
composer require hfw/asana:6.x-dev
php artisan vendor:publish
```

And add this to your `providers` list

```
Helix\Asana\Api\Laravel\AsanaServiceProvider::class
```

See [config/asana.php](config/asana.php) for options.
