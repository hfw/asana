#!/usr/bin/php
<?php

include_once 'init.php';

$project = $api->getProjectTemplate($argv[1])->getInstantiator()
    ->setName(uniqid())
    ->setStart('today')
    ->instantiate()
    ->wait()
    ->getNewProject();

echo $project->getUrl() . "\n";
