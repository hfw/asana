<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use IteratorAggregate;
use Traversable;

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
 * @method User[]       getFollowers    ()
 * @method string       getName         ()
 * @method Workspace    getWorkspace    ()
 *
 * @method bool         hasFollowers    ()
 *
 * @method $this        setColor        (string $color)
 * @method $this        setName         (string $name)
 *
 * @method User[]       selectFollowers (callable $filter) `fn( User $user ): bool`
 */
class Tag extends AbstractEntity implements IteratorAggregate
{

    use CrudTrait;

    const DIR = 'tags';
    const TYPE = 'tag';

    protected const MAP = [
        'followers' => [User::class],
        'workspace' => Workspace::class
    ];

    /**
     * @param array $filter
     * @return Traversable|Task[]
     */
    public function getIterator(array $filter = Task::GET_INCOMPLETE)
    {
        return $this->api->loadEach($this, Task::class, "{$this}/tasks", $filter);
    }

    /**
     * @return null
     */
    final protected function getParentNode()
    {
        return null;
    }

    /**
     * @param array $filter
     * @return Task[]
     */
    public function getTasks(array $filter = Task::GET_INCOMPLETE)
    {
        return iterator_to_array($this->getIterator($filter));
    }
}