#!/usr/bin/php
<?php
include_once 'init.php';
$task = $api->getTask($argv[1]);
$fields = $task->getCustomFields();
$fields[$argv[2]] = strlen($argv[3]) ? $argv[3] : null;
dump($task->toArray(true));
$task->update();