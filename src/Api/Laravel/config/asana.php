<?php

use Helix\Asana\Api;

return [

    /*
    |--------------------------------------------------------------------------
    | Access Token
    |--------------------------------------------------------------------------
    */

    'token' => env('ASANA_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | API Singleton
    |--------------------------------------------------------------------------
    */

    'class' => Api::class,
    'log' => true,
    'workspace' => null,

    /*
    |--------------------------------------------------------------------------
    | Entity Pool and Cache
    |--------------------------------------------------------------------------
    */

    'pool_log' => false,
    'cache' => false,
    'cache_ttl' => 3600,

];
