<?php

include_once 'init.php';

foreach ($api->getDefaultWorkspace()->getUsers() as $user) {
    echo "{$user->getName()} ({$user->getEmail()})\n";
    foreach ($user->getTeams() as $team) {
        echo "- {$team->getName()}\n";
    }
    echo "\n";
}