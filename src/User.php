<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
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
class User extends AbstractEntity
{

    use PostMutatorTrait;

    final protected const DIR = 'users';
    final public const TYPE = 'user';

    protected const MAP = [
        'photo' => Photo::class,
        'workspaces' => [Workspace::class]
    ];

    /**
     * @param Workspace $workspace
     * @return $this
     */
    public function addToWorkspace(Workspace $workspace): static
    {
        return $this->_addWithPost("{$workspace}/addUser", [
            'user' => $this->getGid()
        ], 'workspaces', [$workspace]);
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return Portfolio[]
     */
    public function getFavoritePortfolios(Workspace $workspace = null): array
    {
        return $this->getFavorites(Portfolio::class, $workspace);
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return Project[]
     */
    public function getFavoriteProjects(Workspace $workspace = null): array
    {
        return $this->getFavorites(Project::class, $workspace);
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return Tag[]
     */
    public function getFavoriteTags(Workspace $workspace = null): array
    {
        return $this->getFavorites(Tag::class, $workspace);
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return Team[]
     */
    public function getFavoriteTeams(Workspace $workspace = null): array
    {
        return $this->getFavorites(Team::class, $workspace);
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return User[]
     */
    public function getFavoriteUsers(Workspace $workspace = null): array
    {
        return $this->getFavorites(self::class, $workspace);
    }

    /**
     * @template T of AbstractEntity
     * @param class-string<T> $class
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return T[]
     */
    protected function getFavorites(string $class, Workspace $workspace = null): array
    {
        return $this->api->loadAll($this, $class, "{$this}/favorites", [
            'resource_type' => $class::TYPE, /** @uses AbstractEntity::TYPE */
            'workspace' => ($workspace ?? $this->api->getWorkspace())->getGid()
        ]);
    }

    /**
     * @return string[]
     */
    public function getPoolKeys(): array
    {
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
    public function getPortfolios(Workspace $workspace = null): array
    {
        return $this->api->loadAll($this, Portfolio::class, "portfolios", [
            'workspace' => ($workspace ?? $this->api->getWorkspace())->getGid(),
            'owner' => $this->getGid()
        ]);
    }

    /**
     * @param null|Workspace $workspace Falls back to the default workspace.
     * @return TaskList
     */
    public function getTaskList(Workspace $workspace = null): TaskList
    {
        return $this->api->load($this, TaskList::class, "{$this}/user_task_list", [
            'workspace' => ($workspace ?? $this->api->getWorkspace())->getGid()
        ]);
    }

    /**
     * Returns all tasks in the given workspace that are assigned to the user.
     *
     * @param string[] $filter `workspace` falls back to the default.
     * @return Task[]
     */
    public function getTasks(array $filter = Task::GET_INCOMPLETE): array
    {
        $filter['assignee'] = $this->getGid();
        $filter['workspace'] ??= $this->api->getWorkspace()->getGid();
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
    public function getTeams(Workspace $organization = null): array
    {
        return $this->api->loadAll($this, Team::class, "{$this}/teams", [
            'organization' => ($organization ?? $this->api->getWorkspace())->getGid()
        ]);
    }

    /**
     * @param Workspace $workspace
     * @return $this
     */
    public function removeFromWorkspace(Workspace $workspace): static
    {
        return $this->_removeWithPost("{$workspace}/removeUser", [
            'user' => $this->getGid()
        ], 'workspaces', [$workspace]);
    }

}