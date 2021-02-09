#!/usr/bin/php
<?php

include_once 'init.php';

$taskList = $api->getMe()->getTaskList();
foreach ($taskList as $task) {
    echo "{$task->getUrl()}\n";
}