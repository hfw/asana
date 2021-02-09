#!/usr/bin/php
<?php

use Helix\Asana\Project;

include_once 'init.php';

$me = $api->getMe();
$workspace = $me->getDefaultWorkspace();

$task = $workspace->newTask()
    ->setName('Test')
    ->setNotes('Test task.')
    ->setDueOn((new DateTime())->modify('+1 day'))
    ->setAssignee($me)
    ->setLiked(true)
    ->addFollower($me);

// try to use a project
if ($project = $workspace->getProjects(Project::GET_ACTIVE + ['limit' => 1])[0] ?? null) {
    $task->addToProject($project);
}

// try to use a tag
if ($tag = $workspace->findTags('*', 1)[0] ?? null) {
    $task->addTag($tag);
}

$task->create();

$task->setNotes('Test task -- updated.')->update();

$task->newComment()
    ->setText("Test comment. \u{1F642}")
    ->setLiked(true)
    ->create();

$attachment = $task->addAttachment(__FILE__);

dump($task->getUrl());