<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\UpdateTrait;
use Helix\Asana\Webhook\ProjectWebhook;
use Helix\Asana\Webhook\TaskWebhook;

/**
 * A workspace / organization.
 *
 * Workspaces cannot be created or deleted through the API, but they can be updated.
 *
 * @see https://developers.asana.com/docs/asana-workspaces
 * @see https://developers.asana.com/docs/workspace
 *
 * @method string[]         getEmailDomains     ()
 * @method string           getName             ()
 * @method $this            setName             (string $name)
 *
 * @method CustomField[]    selectCustomFields  (callable $filter) `fn( CustomField $field ): bool`
 * @method Portfolio[]      selectPortfolios    (callable $filter) `fn( Portfolio $portfolio ): bool`
 * @method Project[]        selectProjects      (callable $filter, array $apiFilter = Project::GET_ACTIVE) `fn( Project $project ): bool`
 * @method Tag[]            selectTags          (callable $filter) Active tags. `fn( Tag $tag ): bool`
 * @method Team[]           selectTeams         (callable $filter) `fn( Team $team ): bool`
 * @method User[]           selectUsers         (callable $filter) `fn( User $user ): bool`
 */
class Workspace extends AbstractEntity
{

    use UpdateTrait;

    final protected const DIR = 'workspaces';
    final public const TYPE = 'workspace';

    /**
     * Exports the organization.
     *
     * @see https://developers.asana.com/docs/asana-organization-exports
     *
     * @return OrganizationExport
     */
    public function export(): OrganizationExport
    {
        return $this->api->factory($this, OrganizationExport::class)->create($this);
    }

    /**
     * Finds entities via the typeahead endpoint.
     *
     * > :warning:
     * > This endpoint is fuzzy, so you should prefer using the selection methods for precision.
     * > - Case-insensitive
     * > - No special characters, except a single `*` for "any"
     *
     * > :info:
     * > The typeahead endpoint isn't very good for finding users, so that method doesn't exist in this class.
     *
     * @see https://developers.asana.com/docs/get-objects-via-typeahead
     *
     * @template T of AbstractEntity
     * @param class-string<T> $class
     * @param string $text
     * @param int $limit 1-100
     * @return T[]
     */
    protected function find(string $class, string $text = '*', int $limit = 20): array
    {
        return $this->api->loadAll($this, $class, "{$this}/typeahead", [
            'resource_type' => $class::TYPE,
            'query' => $text,
            'count' => $limit
        ]);
    }

    /**
     * Finds custom fields via the typeahead endpoint.
     *
     * @param string $text
     * @param int $limit 1-100
     * @return CustomField[]
     */
    public function findCustomFields(string $text = '*', int $limit = 20): array
    {
        return $this->find(CustomField::class, $text, $limit);
    }

    /**
     * Finds portfolios via the typeahead endpoint.
     *
     * @param string $text
     * @param int $limit 1-100
     * @return Portfolio[]
     */
    public function findPortfolios(string $text = '*', int $limit = 20): array
    {
        return $this->find(Portfolio::class, $text, $limit);
    }

    /**
     * Finds projects via the typeahead endpoint.
     *
     * @param string $text
     * @param int $limit 1-100
     * @return Project[]
     */
    public function findProjects(string $text = '*', int $limit = 20): array
    {
        return $this->find(Project::class, $text, $limit);
    }

    /**
     * Finds tags via the typeahead endpoint.
     *
     * > :info:
     * > This will search against all tags, unlike {@link getTags()}
     *
     * @param string $text
     * @param int $limit 1-100
     * @return Tag[]
     */
    public function findTags(string $text = '*', int $limit = 20): array
    {
        return $this->find(Tag::class, $text, $limit);
    }

    /**
     * Finds tasks via the typeahead endpoint.
     *
     * @param string $text
     * @param int $limit 1-100
     * @return Task[]
     */
    public function findTasks(string $text = '*', int $limit = 20): array
    {
        return $this->find(Task::class, $text, $limit);
    }

    /**
     * The workspace's custom fields.
     *
     * @see https://developers.asana.com/docs/get-a-workspaces-custom-fields
     *
     * @return CustomField[]
     */
    public function getCustomFields(): array
    {
        return $this->api->loadAll($this, CustomField::class, "{$this}/custom_fields");
    }

    /**
     * The API user's portfolios in this workspace.
     *
     * @see https://developers.asana.com/docs/get-multiple-portfolios
     *
     * @return Portfolio[]
     */
    public function getPortfolios(): array
    {
        return $this->api->loadAll($this, Portfolio::class, "portfolios", [
            'workspace' => $this->getGid(),
            'owner' => $this->api->getMe()->getGid() // the only allowed value, but still required.
        ]);
    }

    /**
     * The workspace's projects.
     *
     * @see https://developers.asana.com/docs/get-multiple-projects
     *
     * @param array $filter
     * @return Project[]
     */
    public function getProjects(array $filter = Project::GET_ACTIVE): array
    {
        $filter['workspace'] = $this->getGid();
        return $this->api->loadAll($this, Project::class, 'projects', $filter);
    }

    /**
     * The workspace's active tags.
     *
     * > :info:
     * > To search for any tag, use {@link findTags()}
     *
     * @see https://developers.asana.com/docs/get-multiple-tags
     *
     * @return Tag[]
     */
    public function getTags(): array
    {
        return $this->api->loadAll($this, Tag::class, 'tags', ['workspace' => $this->getGid()]);
    }

    /**
     * The organization's teams.
     *
     * @see https://developers.asana.com/docs/get-teams-in-an-organization
     *
     * @return Team[]
     */
    public function getTeams(): array
    {
        return $this->api->loadAll($this, Team::class, "organizations/{$this->getGid()}/teams");
    }

    /**
     * Checks the pool before fetching
     *
     * @see User::getPoolKeys()
     *
     * @param string $email
     * @return null|User
     */
    public function getUserByEmail(string $email): ?User
    {
        /** @var null|User $user */
        $user = $this->api->getPool()->get("users/{$email}", $this,
            fn() => $this->selectUsers(
                fn(User $user) => $user->getEmail() === $email
            )[0] ?? null
        );
        return $user;
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        return $this->api->loadAll($this, User::class, "{$this}/users");
    }

    /**
     * @return ProjectWebhook[]|TaskWebhook[]
     */
    public function getWebhooks(): array
    {
        return array_map(
            fn(array $data) => $this->api->getPool()->get($data['gid'], $this,
                fn() => $this->api->factory($this, match ($data['resource_type']) {
                    Project::TYPE => ProjectWebhook::class,
                    Task::TYPE => TaskWebhook::class
                }, $data)
            ),
            $this->api->get('webhooks', ['workspace' => $this->getGid(), 'opt_expand' => 'this'])
        );
    }

    /**
     * @return bool
     */
    final public function isOrganization(): bool
    {
        return $this->_is('is_organization');
    }

    /**
     * Factory.
     *
     * @return CustomField
     */
    public function newCustomField(): CustomField
    {
        return $this->api->factory($this, CustomField::class)->setWorkspace($this);
    }

    /**
     * Factory.
     *
     * @return Portfolio
     */
    public function newPortfolio(): Portfolio
    {
        return $this->api->factory($this, Portfolio::class)->setWorkspace($this);
    }

    /**
     * Factory.
     *
     * @return Project
     */
    public function newProject(): Project
    {
        return $this->api->factory($this, Project::class)->setWorkspace($this);
    }

    /**
     * Factory.
     *
     * @return Tag
     */
    public function newTag(): Tag
    {
        return $this->api->factory($this, Tag::class)->setWorkspace($this);
    }

    /**
     * Factory.
     *
     * @return Task
     */
    public function newTask(): Task
    {
        return $this->api->factory($this, Task::class)->setWorkspace($this);
    }

}