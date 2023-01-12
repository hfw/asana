#!/usr/bin/php
<?php

include_once 'init.php';

foreach ($api->getWorkspace()->getTags() as $tag) {
    echo "{$tag->getName()}\n";
    foreach ($tag as $task) {
        echo "- {$task->getName()}\n";
    }
    echo "\n";
}