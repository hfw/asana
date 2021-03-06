<?php

namespace Helix\Asana\User;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\ImmutableInterface;
use Helix\Asana\Task;
use Helix\Asana\User;
use Helix\Asana\Workspace;
use IteratorAggregate;
use Traversable;

/**
 * A user's task list in a given workspace.
 *
 * @immutable User task lists cannot be altered via the API.
 *
 * @see https://developers.asana.com/docs/asana-user-task-lists
 * @see https://developers.asana.com/docs/user-task-list
 *
 * @method string       getName         ()
 * @method User         getOwner        ()
 * @method Workspace    getWorkspace    ()
 */
class TaskList extends AbstractEntity implements ImmutableInterface, IteratorAggregate {

    const DIR = 'user_task_lists';
    const TYPE = 'user_task_list';

    protected const MAP = [
        'owner' => User::class,
        'workspace' => Workspace::class
    ];

    /**
     * @param array $filter
     * @return Traversable|Task[]
     */
    public function getIterator (array $filter = Task::GET_INCOMPLETE) {
        return $this->api->loadEach($this, Task::class, "{$this}/tasks", $filter);
    }

    public function getPoolKeys () {
        $keys = parent::getPoolKeys();

        /** @see User::getTaskList() */
        $keys[] = "{$this->getOwner()}/user_task_list?workspace={$this->getWorkspace()->getGid()}";

        return $keys;
    }

    /**
     * All of the user's tasks.
     *
     * @see https://developers.asana.com/docs/get-tasks-from-a-user-task-list
     *
     * @param array $filter
     * @return Task[]
     */
    public function getTasks (array $filter = Task::GET_INCOMPLETE) {
        return iterator_to_array($this->getIterator($filter));
    }

    /**
     * @param callable $filter `fn( Task $task ): bool`
     * @param array $apiFilter Given to the API to reduce network load.
     * @return Task[]
     */
    public function selectTasks (callable $filter, array $apiFilter = Task::GET_INCOMPLETE) {
        return $this->_select($this->getIterator($apiFilter), $filter);
    }
}