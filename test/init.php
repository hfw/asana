<?php

use Helix\Asana\Api;
use Helix\Asana\Api\FileCache;
use Helix\Asana\Api\SimpleCachePool;
use Psr\Log\AbstractLogger;

echo "\n";

include_once __DIR__ . '/../vendor/autoload.php';

error_reporting(E_ALL);

set_exception_handler(function(Throwable $throwable) {
    //var_export($throwable);
    echo $throwable . "\n\n";
});

set_error_handler(function(int $code, string $message, string $file, int $line, $ctx) {
    echo "BEGIN ERROR CONTEXT\n";
    var_dump($ctx);
    echo "\nEND ERROR CONTEXT\n\n";
    throw new ErrorException("{$message}", $code, 1, $file, $line);
});

function dump ($mixed) {
    var_dump($mixed);
    echo "\n";
}

$cache = new FileCache(__DIR__ . '/cache');
$pool = new SimpleCachePool($cache);
$api = new Api(getenv('ASANA_TEST_TOKEN'), $pool);
$api->setLog(new class extends AbstractLogger {

    public function log ($level, $msg, array $ctx = []): void {
        static $color = [
            'debug' => 90,
            'error' => 91,
        ];
        echo "\e[{$color[$level]}m{$msg}";
        if ($ctx) {
            echo ' => ';
            var_dump($ctx);
        }
        echo "\e[0m\n\n";
    }

});

//$pool->setTtl(300);
$cache->setLog($api->getLog());