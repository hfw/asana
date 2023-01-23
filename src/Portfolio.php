<?php

namespace Helix\Asana;

use Generator;
use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Base\AbstractEntity\MembersTrait;
use Helix\Asana\Base\AbstractEntity\UrlTrait;
use Helix\Asana\Base\Data;
use Helix\Asana\Base\DateTimeTrait;
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
 * @method string       getName         ()
 * @method User         getOwner        ()
 * @method Workspace    getWorkspace    ()
 *
 * @method $this        setColor        (string $color)
 * @method $this        setMembers      (User[] $members)
 * @method $this        setName         (string $name)
 * @method $this        setOwner        (User $owner)
 */
class Portfolio extends AbstractEntity implements IteratorAggregate
{

    use CrudTrait;
    use DateTimeTrait {
        _getDateTime as getCreatedAtDT;
    }
    use FieldSettingsTrait;
    use MembersTrait;
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
            yield $this->api->factory(static::GRAPH[$data['resource_type']] ?? Data::class, $this, $data);
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
     * @param callable $filter
     * @return Portfolio[]|Project[]
     */
    public function selectItems(callable $filter): array
    {
        return $this->_select($this, $filter);
    }

}