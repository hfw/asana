Helix :: Asana
=========

A fluent PHP library for Asana's REST API

[![php](https://img.shields.io/badge/PHP-~7.4|~8.1-666999)](https://www.php.net)
[![stable](https://poser.pugx.org/hfw/asana/v)](https://packagist.org/packages/hfw/asana)
[![unstable](https://poser.pugx.org/hfw/asana/v/unstable)](https://packagist.org/packages/hfw/asana)
[![build](https://scrutinizer-ci.com/g/hfw/asana/badges/build.png?b=master)](https://scrutinizer-ci.com/g/hfw/asana)
[![score](https://scrutinizer-ci.com/g/hfw/asana/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hfw/asana)
[![downloads](https://poser.pugx.org/hfw/asana/downloads)](https://packagist.org/packages/hfw/asana)
[![license](https://poser.pugx.org/hfw/asana/license)](LICENSE.txt)

Documentation: https://hfw.github.io/asana

```
composer require hfw/asana
```

For Laravel, see [/src/Api/Laravel](src/Api/Laravel)

Introduction
------------

```
use Helix\Asana\Api;

$api = new Api( ACCESS TOKEN );
```

The `Api` instance is the central access point for entities, and pools entities to avoid redundant network calls and prevent object duplication.

It's also used as a library-wide factory. Subclassing `Api` and overriding `factory()` lets you return whatever you want for any given endpoint.

You don't need to call `new` outside of instantiating the `Api` class.
All library objects are injected with the `Api` instance and use `factory()` to give you what you want.

Examples
--------

You
---

```
$me = $api->getMe();

echo $me->getUrl();
```

Workspaces
--------------

```
// if you're in one workspace
$workspace = $me->getDefaultWorkspace();
$workspace = $api->getDefaultWorkspace(); // same thing

// otherwise
$workspace = $api->getWorkspace( GID );
```

Create a Project
----------------

```
$project = $workspace->newProject()
                     ->setName('Test Project')
                     ->setNotes('A test project.')
                     ->setOwner($me)
                     ->create();

echo $project->getUrl();
```

Get a Project
-------------

```
$project = $api->getProject( GID );
```

Create a Task
-------------

```
$task = $project->newTask()
                ->setAssignee($me)
                ->setName('Test Task')
                ->setNotes('A test task.')
                ->create();

echo $task->getUrl();
```

Iterate Your Tasks
------------------
```
$taskList = $me->getTaskList();
foreach ($taskList as $task){
    // ...
}
```

Class Diagram
-------------
[![](https://hfw.github.io/asana/classes.png)](https://hfw.github.io/asana/inherits.html)
