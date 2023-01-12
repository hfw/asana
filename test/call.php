#!/usr/bin/php
<?php
include_once 'init.php';
$entity = $api->load($api, "Helix\\Asana\\{$argv[1]}", $argv[2]);
$args = array_map(fn(string $each) => strlen($each) ? $each : null, array_slice($argv, 4));
$return = $entity->{$argv[3]}(...$args);
if ($entity->isDiff() and method_exists($entity, 'update')) {
    dump($entity->update());
} else {
    dump($return);
}