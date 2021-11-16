<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\ImmutableInterface;

/**
 * A team.
 *
 * @immutable Teams cannot be altered via the API.
 *
 * @see https://developers.asana.com/docs/asana-teams
 * @see https://developers.asana.com/docs/team
 *
 * @method string       getDescription      ()
 * @method string       getName             ()
 * @method Workspace    getOrganization     ()
 */
class Team extends AbstractEntity implements ImmutableInterface
{

    const DIR = 'teams';
    const TYPE = 'team';

    protected const MAP = [
        'organization' => Workspace::class
    ];

    /**
     * @param User $user
     * @return $this
     */
    public function addUser(User $user)
    {
        $this->api->post("{$this}/addUser", ['user' => $user->getGid()]);
        return $this;
    }

    /**
     * The team's projects.
     *
     * @see https://developers.asana.com/docs/get-a-teams-projects
     *
     * @param array $filter
     * @return Project[]
     */
    public function getProjects(array $filter = Project::GET_ACTIVE)
    {
        return $this->api->loadAll($this, Project::class, "{$this}/projects", $filter);
    }

    /**
     * @return string
     */
    final public function getUrl(): string
    {
        return "https://app.asana.com/0/{$this->getGid()}/overview";
    }

    /**
     * The team's users.
     *
     * @see https://developers.asana.com/docs/get-users-in-a-team
     *
     * @return User[]
     */
    public function getUsers()
    {
        return $this->api->loadAll($this, User::class, "{$this}/users");
    }

    /**
     * Factory.
     *
     * @return Project
     */
    public function newProject()
    {
        /** @var Project $project */
        $project = $this->api->factory($this, Project::class, [
            'workspace' => $this->getOrganization()
        ]);
        return $project->setTeam($this);
    }

    /**
     * @param User $user
     * @return $this
     */
    public function removeUser(User $user)
    {
        $this->api->post("{$this}/removeUser", ['user' => $user->getGid()]);
        return $this;
    }
}