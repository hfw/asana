Helix :: Asana
=========

A fluent PHP library for Asana's REST API

[![php](https://img.shields.io/badge/PHP-~8.1-666999)](https://www.php.net)
[![stable](https://poser.pugx.org/hfw/asana/v)](https://packagist.org/packages/hfw/asana)
[![unstable](https://poser.pugx.org/hfw/asana/v/unstable)](https://packagist.org/packages/hfw/asana)
[![build](https://scrutinizer-ci.com/g/hfw/asana/badges/build.png?b=master)](https://scrutinizer-ci.com/g/hfw/asana)
[![score](https://scrutinizer-ci.com/g/hfw/asana/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/hfw/asana)
[![downloads](https://poser.pugx.org/hfw/asana/downloads)](https://packagist.org/packages/hfw/asana)
[![license](https://poser.pugx.org/hfw/asana/license)](LICENSE.txt)

Documentation: https://hfw.github.io/asana

```shell
composer require hfw/asana
```

For Laravel, see [/src/Api/Laravel](src/Api/Laravel)

Introduction
------------

```php
use Helix\Asana\Api;

$api = new Api( ACCESS TOKEN );
```

The `Api` instance is the central access point for entities, and pools entities to avoid redundant network calls and prevent object duplication.

It's also used as a library-wide factory. Subclassing `Api` and overriding `factory()` lets you return whatever you want for any given endpoint.

You don't need to call `new` outside of instantiating the `Api` class.
All library objects are injected with the `Api` instance and use `factory()` to give you what you want.

Example: You
------------

```php
$me = $api->getMe();
echo $me->getUrl();
```

Example: Workspaces
-------------------

```php
// if you're only in one workspace, then it's a safe default
$workspace = $api->getWorkspace();

// or you can set a default
$api->setWorkspace( GID );
$workspace = $api->getWorkspace();

// or you can get a specific workspace any time
$workspace = $api->getWorkspace( GID );
```

Example: Projects
-----------------

```php
// create a project
$project = $workspace->newProject()
                     ->setName('Test Project')
                     ->setNotes('A test project.')
                     ->setOwner($me)
                     ->create();
echo $project->getUrl();

// get a project
$project = $api->getProject( GID );
```

Example: Tasks
--------------

```php
// create a task
$task = $project->newTask()
                ->setAssignee($me)
                ->setName('Test Task')
                ->setNotes('A test task.')
                ->create();
echo $task->getUrl();

// iterate your tasks
$taskList = $me->getTaskList();
foreach ($taskList as $task){
    // ...
}
```

Class Diagram
-------------
[![](https://hfw.github.io/asana/classes.png)](https://hfw.github.io/asana/inherits.html)
