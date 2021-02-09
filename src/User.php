<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\ImmutableInterface;
use Helix\Asana\Base\AbstractEntity\PostMutatorTrait;
use Helix\Asana\User\Photo;
use Helix\Asana\User\TaskList;

/**
 * A user.
 *
 * @immutable Users cannot be altered via the API.
 *
 * @see https://developers.asana.com/docs/asana-users
 * @see https://developers.asana.com/docs/user
 *
 * @method string       getEmail        ()
 * @method string       getName         ()
 * @method null|Photo   getPhoto        ()
 * @method Workspace[]  getWorkspaces   ()
 */
class User extends AbstractEntity implements ImmutableInterface {

    use PostMutatorTrait;

    const DIR = 'users';
    const TYPE = 'user';

    protected const MAP = [
        'photo' => Photo::class,
        'workspaces' => [Workspace::class]
    ];

    /**
     * @param Workspace $workspace
     * @return $this
     */
    public function addToWorkspace (Workspace $workspace) {
        return $this->_addWithPost("{$workspace}/addUser", [
            'user' => $this->getGid()
        ], 'workspaces', [$workspace]);
    }

    /**
     * Returns the first known workspace for the user.
     *
     * @return Workspace
     */
    public function getDefaultWorkspace () {
        return $this->getWorkspaces()[0];
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return Portfolio[]
     */
    public function getFavoritePortfolios (Workspace $workspace = null) {
        return $this->getFavorites(Portfolio::class, Portfolio::TYPE, $workspace);
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return Project[]
     */
    public function getFavoriteProjects (Workspace $workspace = null) {
        return $this->getFavorites(Project::class, Project::TYPE, $workspace);
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return Tag[]
     */
    public function getFavoriteTags (Workspace $workspace = null) {
        return $this->getFavorites(Tag::class, Tag::TYPE, $workspace);
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return Team[]
     */
    public function getFavoriteTeams (Workspace $workspace = null) {
        return $this->getFavorites(Team::class, Team::TYPE, $workspace);
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return User[]
     */
    public function getFavoriteUsers (Workspace $workspace = null) {
        return $this->getFavorites(self::class, self::TYPE, $workspace);
    }

    /**
     * @param string $class
     * @param string $resourceType
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return array
     */
    protected function getFavorites (string $class, string $resourceType, Workspace $workspace = null) {
        return $this->api->loadAll($this, $class, "{$this}/favorites", [
            'resource_type' => $resourceType,
            'workspace' => ($workspace ?? $this->api->getDefaultWorkspace())->getGid()
        ]);
    }

    /**
     * @return string[]
     */
    public function getPoolKeys () {
        $keys = parent::getPoolKeys();

        // include email as a key if it's loaded
        if (isset($this->data['email'])) {
            $keys[] = "users/{$this->data['email']}";
        }

        return $keys;
    }

    /**
     * @param null|Workspace $workspace
     * @return Portfolio[]
     */
    public function getPortfolios (Workspace $workspace = null) {
        return $this->api->loadAll($this, Portfolio::class, "portfolios", [
            'workspace' => ($workspace ?? $this->api->getDefaultWorkspace())->getGid(),
            'owner' => $this->getGid()
        ]);
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return TaskList
     */
    public function getTaskList (Workspace $workspace = null) {
        return $this->api->load($this, TaskList::class, "{$this}/user_task_list", [
            'workspace' => ($workspace ?? $this->api->getDefaultWorkspace())->getGid()
        ]);
    }

    /**
     * Returns all tasks in the given workspace that are assigned to the user.
     *
     * @param string[] $filter `workspace` falls back to the default.
     * @return Task[]
     */
    public function getTasks (array $filter = Task::GET_INCOMPLETE) {
        $filter['assignee'] = $this->getGid();
        $filter += ['workspace' => $this->api->getDefaultWorkspace()->getGid()];
        return $this->api->loadAll($this, Task::class, 'tasks', $filter);
    }

    /**
     * The user's teams.
     *
     * @see  https://developers.asana.com/docs/get-teams-for-a-user
     *
     * @param null|Workspace $organization Falls back to the default workspace.
     * @return Team[]
     */
    public function getTeams (Workspace $organization = null) {
        return $this->api->loadAll($this, Team::class, "{$this}/teams", [
            'organization' => ($organization ?? $this->getDefaultWorkspace())->getGid()
        ]);
    }

    /**
     * @return string
     */
    final public function getUrl (): string {
        return "https://app.asana.com/0/{$this->getGid()}/list";
    }

    /**
     * @param Workspace $workspace
     * @return $this
     */
    public function removeFromWorkspace (Workspace $workspace) {
        return $this->_removeWithPost("{$workspace}/removeUser", [
            'user' => $this->getGid()
        ], 'workspaces', [$workspace]);
    }

}