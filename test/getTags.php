<?php

include_once 'init.php';

foreach ($api->getDefaultWorkspace()->getTags() as $tag) {
    echo "{$tag->getName()}\n";
    foreach ($tag as $task) {
        echo "- {$task->getName()}\n";
    }
    echo "\n";
}