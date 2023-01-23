#!/usr/bin/php
<?php

use Helix\Asana\Api;
use Helix\Asana\Api\AsanaError;
use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Project;
use Helix\Asana\Task;

include_once 'init.php';

if (!is_dir('cache')) {
    mkdir('cache');
}

$class = $argv[1];
$path = $argv[2];
$updateToken = isset($argv[3]);

/** @var Api $api */
/** @var Project|Task $entity */
$entity = $api->load($api, "Helix\\Asana\\" . $class, $path);
assert(method_exists($entity, 'getEvents'));
$gid = $entity->getGid();
$tokenFile = "cache/{$gid}.sync";

RETRY:
if (!file_exists($tokenFile)) {
    $token = null;
    $entity->getEvents($token);
    file_put_contents($tokenFile, $token);
    echo "Sync token written. Check back after doing something.\n\n";
    exit;
}
try {
    $token = file_get_contents($tokenFile);
    $events = $entity->getEvents($token);
} catch (AsanaError $error) {
    if ($error->is(412)) {
        unlink($tokenFile);
        goto RETRY;
    }
    throw $error;
}

foreach ($events as $event) {
    $when = $event->getCreatedAt();
    $who = $event->hasUser() ? $event->getUser()->getName() : 'system';
    $verb = $event->getAction();
    $target = $event->getResource();
    $targetPath = $target instanceof AbstractEntity ? "{$target} ({$target->getName()})" : "{$target['resource_type']}/{$target['gid']}";
    if ($parent = $event->getParent()) {
        $parentPath = $parent instanceof AbstractEntity ? "{$parent} ({$parent->getName()})" : "{$parent['resource_type']}/{$parent['gid']}";
        $effect = "in {$parentPath} << " . json_encode($target);
    } elseif ($change = $event->getChange()) {
        $effect = ":: {$change->getAction()} {$change->getField()} << " . json_encode($change->getPayload());
    } else {
        $effect = '';
    }
    printf("[%s] %s %s %s %s\n\n",
        $when, $who, $verb, $targetPath, $effect
    );
}

if ($updateToken) {
    file_put_contents($tokenFile, $token);
}