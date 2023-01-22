<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\UrlTrait;
use Helix\Asana\Team\ProjectTemplate;

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
class Team extends AbstractEntity
{

    use UrlTrait;

    final protected const DIR = 'teams';
    final public const TYPE = 'team';

    protected const MAP = [
        'organization' => Workspace::class
    ];

    /**
     * @param User $user
     * @return $this
     */
    public function addUser(User $user): static
    {
        $this->api->post("{$this}/addUser", ['user' => $user->getGid()]);
        return $this;
    }

    /**
     * @return ProjectTemplate[]
     */
    public function getProjectTemplates(): array
    {
        return $this->api->loadAll($this, ProjectTemplate::class, "{$this}/project_templates");
    }

    /**
     * The team's projects.
     *
     * @see https://developers.asana.com/docs/get-a-teams-projects
     *
     * @param array $filter
     * @return Project[]
     */
    public function getProjects(array $filter = Project::GET_ACTIVE): array
    {
        return $this->api->loadAll($this, Project::class, "{$this}/projects", $filter);
    }

    /**
     * The team's users.
     *
     * @see https://developers.asana.com/docs/get-users-in-a-team
     *
     * @return User[]
     */
    public function getUsers(): array
    {
        return $this->api->loadAll($this, User::class, "{$this}/users");
    }

    /**
     * Factory.
     *
     * @return Project
     */
    public function newProject(): Project
    {
        return $this->api->factory(Project::class, $this, [
            'workspace' => $this->getOrganization()
        ])->setTeam($this);
    }

    /**
     * @param User $user
     * @return $this
     */
    public function removeUser(User $user): static
    {
        $this->api->post("{$this}/removeUser", ['user' => $user->getGid()]);
        return $this;
    }
}