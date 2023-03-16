<?php

namespace Helix\Asana\Api\Laravel;

use Helix\Asana\Api;
use Helix\Asana\Api\Laravel\Command\AsanaCall;
use Helix\Asana\Api\Laravel\Command\AsanaGet;
use Helix\Asana\Api\Laravel\Command\AsanaTest;
use Helix\Asana\Api\Pool;
use Helix\Asana\Api\SimpleCachePool;
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

    /**
     * @param array $config
     * @return Api
     */
    protected function getApi(array $config): Api
    {
        $apiClass = $config['class'] ?? Api::class;
        assert(is_a($apiClass, Api::class, true));
        $api = new $apiClass($config['token'], $this->getPool($config));
        $api->setLog(($config['log'] ?? true) ? Log::getFacadeRoot() : null);
        $api->setWorkspace($config['workspace'] ?? null);
        return $api;
    }

    /**
     * @param array $config
     * @return Pool
     */
    protected function getPool(array $config): Pool
    {
        if ($config['cache'] ?? false) {
            $pool = new SimpleCachePool(Cache::store());
            $pool->setTtl($config['cache_ttl'] ?? 3600);
        } else {
            $pool = new Pool();
        }
        $pool->setLog(($config['pool_log'] ?? false) ? Log::getFacadeRoot() : null);
        return $pool;
    }

    public function provides()
    {
        return [static::NAME];
    }

    public function register()
    {
        $this->app->singleton(static::NAME, fn() => $this->getApi($this->app['config'][static::NAME]));
    }
}
