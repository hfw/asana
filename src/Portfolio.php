<?php

namespace Helix\Asana;

use Generator;
use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Base\AbstractEntity\PostMutatorTrait;
use Helix\Asana\Base\AbstractEntity\UrlTrait;
use Helix\Asana\Base\Data;
use Helix\Asana\CustomField\FieldSetting;
use Helix\Asana\CustomField\FieldSettingsTrait;
use IteratorAggregate;

/**
 * A portfolio.
 *
 * Portfolios act like directories, they contain projects and other portfolios (non-circular).
 *
 * @see https://developers.asana.com/docs/asana-portfolios
 * @see https://developers.asana.com/docs/portfolio
 *
 * @see Workspace::newPortfolio()
 *
 * @method $this        setWorkspace    (Workspace $workspace) @depends create-only
 *
 * @method string       getColor        ()
 * @method string       getCreatedAt    () RFC3339x
 * @method User         getCreatedBy    ()
 * @method User[]       getMembers      ()
 * @method string       getName         ()
 * @method User         getOwner        ()
 * @method Workspace    getWorkspace    ()
 *
 * @method bool         hasMembers      ()
 *
 * @method $this        setColor        (string $color)
 * @method $this        setMembers      (User[] $members)
 * @method $this        setName         (string $name)
 * @method $this        setOwner        (User $owner)
 *
 * @method User[]       selectMembers   (callable $filter) `fn( User $user ): bool`
 */
class Portfolio extends AbstractEntity implements IteratorAggregate
{

    use CrudTrait;
    use FieldSettingsTrait;
    use PostMutatorTrait;
    use UrlTrait;

    final protected const DIR = 'portfolios';
    final public const TYPE = 'portfolio';

    /**
     * Any resource types that are not present here will fall back to becoming {@link Data}
     */
    protected const GRAPH = [
        self::TYPE => self::class,
        Project::TYPE => Project::class,
    ];

    protected const MAP = [
        'created_by' => User::class,
        'custom_field_settings' => [FieldSetting::class],
        'owner' => User::class,
        'members' => [User::class],
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
     * @see https://developers.asana.com/docs/add-a-portfolio-item
     * @param Portfolio|Project $item
     * @return $this
     */
    public function addItem(Portfolio|Project $item): static
    {
        $this->api->post("{$this}/addItem", ['item' => $item->getGid()]);
        return $this;
    }

    /**
     * @param iterable<Portfolio|Project> $items
     * @return $this
     */
    public function addItems(iterable $items): static
    {
        foreach ($items as $item) {
            $this->addItem($item);
        }
        return $this;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function addMember(User $user): static
    {
        return $this->addMembers([$user]);
    }

    /**
     * @see https://developers.asana.com/docs/add-users-to-a-portfolio
     * @param User[] $users
     * @return $this
     */
    public function addMembers(array $users): static
    {
        return $this->_addWithPost("{$this}/addMembers", [
            'members' => array_column($users, 'gid')
        ], 'members', $users);
    }

    /**
     * @return Portfolio[]|Project[]
     */
    public function getItems(): array
    {
        return iterator_to_array($this->getIterator());
    }

    /**
     * No API filter is available.
     *
     * @return Generator<Portfolio|Project>
     */
    public function getIterator(): Generator
    {
        foreach ($this->api->getEach("{$this}/items") as $data) {
            yield $this->api->factory($this, static::GRAPH[$data['resource_type']] ?? Data::class, $data);
        }
    }

    /**
     * @return Project[]
     */
    public function getProjects(): array
    {
        return iterator_to_array($this);
    }

    /**
     * @param User $user
     * @return $this
     */
    public function removeMember(User $user): static
    {
        return $this->removeMembers([$user]);
    }

    /**
     * @see https://developers.asana.com/docs/remove-users-from-a-portfolio
     * @param User[] $users
     * @return $this
     */
    public function removeMembers(array $users): static
    {
        return $this->_removeWithPost("{$this}/removeMembers", [
            'members' => array_column($users, 'gid')
        ], 'members', $users);
    }

    /**
     * @param callable $filter
     * @return Portfolio[]|Project[]
     */
    public function selectItems(callable $filter): array
    {
        return $this->_select($this, $filter);
    }

}