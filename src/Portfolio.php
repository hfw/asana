<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Base\AbstractEntity\PostMutatorTrait;
use Helix\Asana\CustomField\FieldSetting;
use Helix\Asana\CustomField\FieldSettingsTrait;
use IteratorAggregate;
use Traversable;

/**
 * A portfolio.
 *
 * This is only available to business/enterprise accounts.
 *
 * So...
 *
 * I haven't tested this.
 *
 * If you have a biz account you want to let me play on, please contact me.
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
class Portfolio extends AbstractEntity implements IteratorAggregate {

    use CrudTrait;
    use FieldSettingsTrait;
    use PostMutatorTrait;

    const DIR = 'portfolios';
    const TYPE = 'portfolio';

    protected const MAP = [
        'created_by' => User::class,
        'custom_field_settings' => [FieldSetting::class],
        'owner' => User::class,
        'members' => [User::class],
        'workspace' => Workspace::class
    ];

    /**
     * @param User $user
     * @return $this
     */
    public function addMember (User $user) {
        return $this->addMembers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function addMembers (array $users) {
        return $this->_addWithPost("{$this}/addMembers", [
            'members' => array_column($users, 'gid')
        ], 'members', $users);
    }

    /**
     * @param Project $project
     * @return $this
     */
    public function addProject (Project $project) {
        $this->api->post("{$this}/addItem", ['item' => $project->getGid()]);
        return $this;
    }

    /**
     * No API filter is available.
     *
     * @return Traversable|Project[]
     */
    public function getIterator () {
        return $this->api->loadEach($this, Project::class, "{$this}/items");
    }

    /**
     * @return null
     */
    final protected function getParentNode () {
        return null;
    }

    /**
     * @return Project[]
     */
    public function getProjects () {
        return iterator_to_array($this);
    }

    /**
     * @param User $user
     * @return $this
     */
    public function removeMember (User $user) {
        return $this->removeMembers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function removeMembers (array $users) {
        return $this->_removeWithPost("{$this}/removeMembers", [
            'members' => array_column($users, 'gid')
        ], 'members', $users);
    }

    /**
     * @param Project $project
     * @return $this
     */
    public function removeProject (Project $project) {
        $this->api->post("{$this}/removeItem", ['item' => $project->getGid()]);
        return $this;
    }

    /**
     * @param callable $filter `fn( Project $project ): bool`
     * @return Project[]
     */
    public function selectProjects (callable $filter) {
        return $this->_select($this, $filter);
    }
}