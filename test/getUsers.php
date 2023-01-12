#!/usr/bin/php
<?php

include_once 'init.php';

foreach ($api->getWorkspace()->getUsers() as $user) {
    echo "{$user->getName()} ({$user->getEmail()})\n";
    foreach ($user->getTeams() as $team) {
        echo "- {$team->getName()}\n";
    }
    echo "\n";
}