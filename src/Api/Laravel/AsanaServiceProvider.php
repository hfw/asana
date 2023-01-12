<?php

namespace Helix\Asana\Api\Laravel;

use Helix\Asana\Api;
use Helix\Asana\Api\Laravel\Command\AsanaCall;
use Helix\Asana\Api\Laravel\Command\AsanaGet;
use Helix\Asana\Api\Laravel\Command\AsanaTest;
use Helix\Asana\Api\SimpleCachePool;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AsanaServiceProvider extends ServiceProvider implements DeferrableProvider
{

    public const NAME = 'asana';

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/asana.php' => $this->app->configPath('asana.php')
        ]);
        if ($this->app->runningInConsole()) {
            $this->commands([
                AsanaTest::class,
                AsanaGet::class,
                AsanaCall::class,
            ]);
        }
    }

    public function provides()
    {
        return [self::NAME];
    }

    public function register()
    {
        $this->app->singleton(self::NAME, function (Application $app) {
            $config = $app['config'][self::NAME];
            $pool = null;
            if ($config['cache'] ?? false) {
                $pool = new SimpleCachePool(Cache::store());
                $pool->setTtl($config['cache_ttl'] ?? 3600);
            }
            $api = new Api($config['token'], $pool);
            $api->setLog(Log::getFacadeRoot());
            return $api;
        });
    }
}