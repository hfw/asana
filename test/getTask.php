#!/usr/bin/php
<?php
include_once 'init.php';
$task = $api->getTask($argv[1]);
dump($task);
dump($task->getProjects());
dump($task->getUrl());
