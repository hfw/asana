<?php

include_once __DIR__ . '/../init.php';

$me = $api->getMe();
foreach ($me->getWorkspaces() as $workspace) {
    foreach ($workspace->getProjects([]) as $project) {
        $project->delete();
    }
    foreach ($me->getTaskList() as $task) {
        $task->delete();
    }
}
