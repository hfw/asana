<?php

namespace Helix\Asana;

use Generator;
use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Base\AbstractEntity\DueTrait;
use Helix\Asana\Base\AbstractEntity\PostMutatorTrait;
use Helix\Asana\Base\AbstractEntity\SyncTrait;
use Helix\Asana\Base\AbstractEntity\UrlTrait;
use Helix\Asana\Base\DateTimeTrait;
use Helix\Asana\CustomField\FieldSetting;
use Helix\Asana\CustomField\FieldSettingsTrait;
use Helix\Asana\Project\Section;
use Helix\Asana\Project\Status;
use Helix\Asana\Project\TaskCounts;
use Helix\Asana\Webhook\ProjectWebhook;
use IteratorAggregate;

/**
 * A project.
 *
 * @see https://developers.asana.com/docs/asana-projects
 * @see https://developers.asana.com/docs/project
 *
 * @see Workspace::newProject()
 * @see Team::newProject()
 *
 * @method $this        setTeam             (?Team $team)           @depends create-only
 * @method $this        setWorkspace        (Workspace $workspace)  @depends create-only
 *
 * @method bool         isArchived          ()
 * @method string       getColor            ()
 * @method string       getCreatedAt        () RFC3339x
 * @method null|Status  getCurrentStatus    ()
 * @method string       getDefaultView      () See the layout constants.
 * @method User[]       getFollowers        ()
 * @method null|string  getIcon             () read-only
 * @method User[]       getMembers          ()
 * @method string       getModifiedAt       () RFC3339x
 * @method string       getName             ()
 * @method string       getNotes            ()
 * @method null|User    getOwner            ()
 * @method bool         isPublic            ()
 * @method null|Team    getTeam             ()
 * @method Workspace    getWorkspace        ()
 *
 * @method bool         hasFollowers        ()
 * @method bool         hasMembers          ()
 * @method bool         hasOwner            ()
 * @method bool         hasTeam             ()
 *
 * @method $this        setArchived         (bool $archived)
 * @method $this        setColor            (string $color)
 * @method $this        setDefaultView      (string $layout) See the layout constants.
 * @method $this        setName             (string $name)
 * @method $this        setNotes            (string $notes)
 * @method $this        setOwner            (?User $owner)
 * @method $this        setPublic           (bool $public)
 *
 * @method User[]       selectFollowers     (callable $filter) `fn( User $user ): bool`
 * @method User[]       selectMembers       (callable $filter) `fn( User $user ): bool`
 * @method Section[]    selectSections      (callable $filter) `fn( Section $section ): bool`
 * @method Status[]     selectStatuses      (callable $filter) `fn( Status $status ): bool`
 * @method Task[]       selectTasks         (callable $filter, array $apiFilter = Task::GET_INCOMPLETE) `fn( Task $task ): bool`
 */
class Project extends AbstractEntity implements IteratorAggregate
{

    use CrudTrait;
    use DateTimeTrait {
        _getDateTime as getCreatedAtDT;
        _getDateTime as getModifiedAtDT;
    }
    use DueTrait;
    use FieldSettingsTrait;
    use PostMutatorTrait;
    use SyncTrait;
    use UrlTrait;

    final protected const DIR = 'projects';
    final public const TYPE = 'project';

    final public const LAYOUT_BOARD = 'board';
    final public const LAYOUT_CALENDAR = 'calendar';
    final public const LAYOUT_LIST = 'list';
    final public const LAYOUT_TIMELINE = 'timeline';

    final public const GET_ACTIVE = ['archived' => false];
    final public const GET_ARCHIVED = ['archived' => true];

    protected const MAP = [
        'current_status' => Status::class,
        'custom_field_settings' => [FieldSetting::class],
        'followers' => [User::class],
        'members' => [User::class],
        'owner' => User::class,
        'team' => Team::class,
        'workspace' => Workspace::class
    ];

    /**
     * @var Section
     */
    private readonly Section $defaultSection;

    /**
     * @return null
     */
    final protected function _getParentNode()
    {
        return null;
    }

    /**
     * @param array $data
     * @return void
     */
    protected function _setData(array $data): void
    {
        // this is always empty. fields are in the settings, values are in tasks.
        unset($data['custom_fields']);

        // deprecated for due_on
        unset($data['due_date']);

        parent::_setData($data);
    }

    /**
     * @param User $user
     * @return $this
     */
    public function addFollower(User $user): static
    {
        return $this->addFollowers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function addFollowers(array $users): static
    {
        return $this->_addWithPost("{$this}/addFollowers", [
            'followers' => array_column($users, 'gid')
        ], 'followers', $users);
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
     * Duplicates the project.
     *
     * @see https://developers.asana.com/docs/duplicate-a-project
     *
     * If `$team` is `null`, the duplicate will inherit the existing team.
     *
     * If `$schedule` is given:
     * - It must have either `due_on` or `start_on`
     * - `task_dates` is automatically added to `$include`
     * - `should_skip_weekends` defaults to `true` if not given.
     *
     * @param string $name
     * @param string[] $include
     * @param null|Team $team
     * @param array $schedule
     * @return Job
     */
    public function duplicate(string $name, array $include, Team $team = null, array $schedule = []): Job
    {
        $data = ['name' => $name];
        if ($team) {
            $data['team'] = $team->getGid();
        }
        if ($schedule) {
            $include[] = 'task_dates';
            $schedule += ['should_skip_weekends' => true];
            $data['schedule_dates'] = $schedule;
        }
        $data['include'] = array_values($include);
        $remote = $this->api->post("{$this}/duplicate", $data);
        return $this->api->factory($this, Job::class, $remote);
    }

    /**
     * @return Section
     */
    public function getDefaultSection(): Section
    {
        return $this->defaultSection ??= $this->getSections(1)[0];
    }

    /**
     * Iterates over sections.
     *
     * @see https://developers.asana.com/docs/get-sections-in-a-project
     *
     * @param int $limit
     * @return Generator<Section>
     */
    public function getIterator(int $limit = PHP_INT_MAX): Generator
    {
        return $this->api->loadEach($this, Section::class, "{$this}/sections", ['limit' => $limit]);
    }

    /**
     * @param int $limit
     * @return Section[]
     */
    public function getSections(int $limit = PHP_INT_MAX): array
    {
        return iterator_to_array($this->getIterator($limit));
    }

    /**
     * @return Status[]
     */
    public function getStatuses(): array
    {
        return $this->api->loadAll($this, Status::class, "{$this}/project_statuses");
    }

    /**
     * @return TaskCounts
     */
    public function getTaskCounts(): TaskCounts
    {
        $remote = $this->api->get("{$this}/task_counts", [
            'opt_fields' => // opt_expand doesn't work.
                'num_completed_milestones,'
                . 'num_completed_tasks,'
                . 'num_incomplete_milestones,'
                . 'num_incomplete_tasks,'
                . 'num_milestones,'
                . 'num_tasks'
        ]);
        return $this->api->factory($this, TaskCounts::class, $remote);
    }

    /**
     * The project's tasks.
     *
     * @param array $filter
     * @return Task[]
     */
    public function getTasks(array $filter = Task::GET_INCOMPLETE): array
    {
        $filter['project'] = $this->getGid();
        return $this->api->loadAll($this, Task::class, "tasks", $filter);
    }

    /**
     * @return ProjectWebhook[]
     */
    public function getWebhooks(): array
    {
        return $this->api->loadAll($this, ProjectWebhook::class, 'webhooks', [
            'workspace' => $this->getWorkspace()->getGid(),
            'resource' => $this->getGid()
        ]);
    }

    /**
     * @return bool
     */
    final public function isTemplate(): bool
    {
        return $this->_is('is_template');
    }

    /**
     * Factory.
     *
     * @return Section
     */
    public function newSection(): Section
    {
        return $this->api->factory($this, Section::class, ['project' => $this]);
    }

    /**
     * Factory.
     *
     * @return Status
     */
    public function newStatus(): Status
    {
        return $this->api->factory($this, Status::class);
    }

    /**
     * Factory.
     *
     * @return Task
     */
    public function newTask(): Task
    {
        return $this->getDefaultSection()->newTask();
    }

    /**
     * Factory.
     *
     * @return ProjectWebhook
     */
    public function newWebhook(): ProjectWebhook
    {
        return $this->api->factory($this, ProjectWebhook::class)->setResource($this);
    }

    /**
     * @param User $user
     * @return $this
     */
    public function removeFollower(User $user): static
    {
        return $this->removeFollowers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function removeFollowers(array $users): static
    {
        return $this->_removeWithPost("{$this}/removeFollowers", [
            'followers' => array_column($users, 'gid')
        ], 'followers', $users);
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
     * @param User[] $users
     * @return $this
     */
    public function removeMembers(array $users): static
    {
        return $this->_removeWithPost("{$this}/removeMembers", [
            'members' => array_column($users, 'gid')
        ], 'members', $users);
    }
}