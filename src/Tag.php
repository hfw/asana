<?php

namespace Helix\Asana;

use Generator;
use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Base\AbstractEntity\FollowersTrait;
use Helix\Asana\Base\AbstractEntity\TaskIteratorTrait;
use Helix\Asana\Base\AbstractEntity\UrlTrait;
use Helix\Asana\Base\DateTimeTrait;
use IteratorAggregate;

/**
 * A tag.
 *
 * @see https://developers.asana.com/docs/asana-tags
 * @see https://developers.asana.com/docs/tag
 *
 * @see Workspace::newTag()
 *
 * @method $this        setWorkspace    (Workspace $workspace) @depends create-only
 *
 * @method string       getColor        ()
 * @method string       getCreatedAt    () RFC3339x
 * @method string       getName         ()
 * @method Workspace    getWorkspace    ()
 *
 * @method $this        setColor        (string $color)
 * @method $this        setName         (string $name)
 */
class Tag extends AbstractEntity implements IteratorAggregate
{

    use CrudTrait;
    use DateTimeTrait {
        _getDateTime as getCreatedAtDT;
    }
    use FollowersTrait;
    use TaskIteratorTrait;
    use UrlTrait;

    final protected const DIR = 'tags';
    final public const TYPE = 'tag';

    protected const MAP = [
        'followers' => [User::class],
        'workspace' => Workspace::class
    ];

    /**
     * @return null
     */
    final protected function _getParentNode()
    {
        return null;
    }

    /**
     * @param array $filter
     * @return Generator<Task>
     */
    public function getIterator(array $filter = Task::GET_INCOMPLETE): Generator
    {
        return $this->api->loadEach($this, Task::class, "{$this}/tasks", $filter);
    }

}
