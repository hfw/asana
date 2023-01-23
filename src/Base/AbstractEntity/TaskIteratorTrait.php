<?php

namespace Helix\Asana\Base\AbstractEntity;

use Generator;
use Helix\Asana\Task;

/**
 * The resource can be iterated directly for tasks.
 *
 * @method Task[] selectTasks (callable $filter, array $apiFilter = Task::GET_INCOMPLETE) `fn( Task $task ): bool`
 */
trait TaskIteratorTrait
{

    /**
     * @param array $filter
     * @return Generator<Task>
     */
    abstract public function getIterator(array $filter = Task::GET_INCOMPLETE): Generator;

    /**
     * @param array $filter
     * @return Task[]
     */
    public function getTasks(array $filter = Task::GET_INCOMPLETE): array
    {
        return iterator_to_array($this->getIterator($filter));
    }

}
