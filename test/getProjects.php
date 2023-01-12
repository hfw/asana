#!/usr/bin/php
<?php

include_once 'init.php';

foreach ($api->getWorkspace()->getProjects() as $project) {
    echo "{$project->getName()}\n";
    echo "{$project->getTaskCounts()->getNumTasks()} tasks.\n";
    foreach ($project as $section) {
        echo "-- {$section->getName()}\n";
        foreach ($section as $task) {
            echo "---- {$task->getName()}\n";
        }
    }
}