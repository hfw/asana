<?php

namespace Helix\Asana\Project;

use Generator;
use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Base\AbstractEntity\TaskIteratorTrait;
use Helix\Asana\Base\DateTimeTrait;
use Helix\Asana\Project;
use Helix\Asana\Task;
use IteratorAggregate;

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
class Section extends AbstractEntity implements IteratorAggregate
{

    use CrudTrait;
    use DateTimeTrait {
        _getDateTime as getCreatedAtDT;
    }
    use TaskIteratorTrait;

    final protected const DIR = 'sections';
    final public const TYPE = 'section';

    protected const MAP = [
        'project' => Project::class
    ];

    /**
     * @return Project
     */
    final protected function _getParentNode(): Project
    {
        return $this->getProject();
    }

    /**
     * @param array $data
     * @return void
     */
    protected function _setData(array $data): void
    {
        // deprecated for the singular project field.
        unset($data['projects']);

        parent::_setData($data);
    }

    /**
     * @param array $filter
     * @return Generator<Task>
     */
    public function getIterator(array $filter = Task::GET_INCOMPLETE): Generator
    {
        $filter['section'] = $this->getGid();
        return $this->api->loadEach($this, Task::class, 'tasks', $filter);
    }

    /**
     * Factory.
     *
     * @return Task
     */
    public function newTask(): Task
    {
        return $this->api->factory(Task::class, $this)->addToProject($this);
    }
}