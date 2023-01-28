#!/usr/bin/php
<?php

use Helix\Asana\User;

include_once 'init.php';
$task = $api->getTask($argv[1]);
$fields = $task->getCustomFields();
$entry = $fields->getEntry($argv[2]);
dump($entry);
$newValue = strlen($argv[3]) ? $argv[3] : null;

// test types
if ($entry->ofMultiEnum() or $entry->ofPeople()) {
    // gids or labels for enums, gids for people
    $newValue = array_filter(explode(',', (string)$newValue));
    if ($entry->ofPeople()) {
        $newValue = $api->getWorkspace()->selectUsers(
            fn(User $user) => in_array($user->getGid(), $newValue)
        );
    }
}

// full array-access test
$fields[$argv[2]] = $newValue;
dump($fields->getEntry($argv[2])->getDisplayValue());
dump($task->toArray(true));
$task->update();
