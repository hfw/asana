#!/usr/bin/php
<?php

use Helix\Asana\Color;
use Helix\Asana\Project;
use Helix\Asana\Project\Status;

include_once 'init.php';

$me = $api->getMe();
$workspace = $api->getWorkspace();
$team = $workspace->getTeams()[0] ?? null;

$project = $workspace->newProject()
    ->setName('Test Project')
    ->setOwner($me)
    ->setNotes('A test project.')
    ->setColor(Color::random())
    ->setDefaultView(Project::LAYOUT_LIST)
    ->setTeam($team)
    ->create();

$project->setNotes('A test project -- updated.')->update();
$project->addMember($me);

$status = $project->newStatus()
    ->setColor(Status::COLOR_GREEN)
    ->setText('test')
    ->setTitle('status title')
    ->create();

$project->newSection()->setName('Test Section')->create();

dump($project->getUrl());