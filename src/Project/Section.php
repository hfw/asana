<?php

namespace Helix\Asana\Project;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Project;
use Helix\Asana\Task;
use IteratorAggregate;
use Traversable;

/**
 * A project section.
 *
 * @see https://developers.asana.com/docs/asana-sections
 * @see https://developers.asana.com/docs/section
 *
 * @method string   getCreatedAt    () RFC3339x
 * @method string   getName         ()
 * @method Project  getProject      ()
 *
 * @method $this    setName         (string $name)
 */
class Section extends AbstractEntity implements IteratorAggregate {

    use CrudTrait;

    const DIR = 'sections';
    const TYPE = 'section';

    protected const MAP = [
        'project' => Project::class
    ];

    /**
     * @var Project
     */
    protected $parent;

    public function __construct ($caller, array $data = []) {
        parent::__construct($caller, $data);
        $this->parent = $this->getProject();
    }

    protected function _setData (array $data): void {
        // deprecated for the singular project field.
        unset($data['projects']);

        parent::_setData($data);
    }

    /**
     * @param array $filter
     * @return Traversable|Task[]
     */
    public function getIterator (array $filter = Task::GET_INCOMPLETE) {
        $filter['section'] = $this->getGid();
        return $this->api->loadEach($this, Task::class, 'tasks', $filter);
    }

    /**
     * @param array $filter
     * @return Task[]
     */
    public function getTasks (array $filter = Task::GET_INCOMPLETE) {
        return iterator_to_array($this->getIterator($filter));
    }

    /**
     * Factory.
     *
     * @return Task
     */
    public function newTask () {
        /** @var Task $task */
        $task = $this->api->factory($this, Task::class);
        return $task->addToProject($this);
    }
}