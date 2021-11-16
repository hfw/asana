<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Base\AbstractEntity\DateTrait;
use Helix\Asana\Base\AbstractEntity\PostMutatorTrait;
use Helix\Asana\Base\AbstractEntity\SyncTrait;
use Helix\Asana\CustomField\FieldSetting;
use Helix\Asana\CustomField\FieldSettingsTrait;
use Helix\Asana\Project\Section;
use Helix\Asana\Project\Status;
use Helix\Asana\Project\TaskCounts;
use Helix\Asana\Webhook\ProjectWebhook;
use IteratorAggregate;
use Traversable;

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
    use DateTrait;
    use FieldSettingsTrait;
    use PostMutatorTrait;
    use SyncTrait;

    const DIR = 'projects';
    const TYPE = 'project';

    const LAYOUT_BOARD = 'board';
    const LAYOUT_CALENDAR = 'calendar';
    const LAYOUT_LIST = 'list';
    const LAYOUT_TIMELINE = 'timeline';

    const GET_ACTIVE = ['archived' => false];
    const GET_ARCHIVED = ['archived' => true];

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
    private $defaultSection;

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
    public function addFollower(User $user)
    {
        return $this->addFollowers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function addFollowers(array $users)
    {
        return $this->_addWithPost("{$this}/addFollowers", [
            'followers' => array_column($users, 'gid')
        ], 'followers', $users);
    }

    /**
     * @param User $user
     * @return $this
     */
    public function addMember(User $user)
    {
        return $this->addMembers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function addMembers(array $users)
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
    public function duplicate(string $name, array $include, Team $team = null, array $schedule = [])
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
        /** @var array $remote */
        $remote = $this->api->post("{$this}/duplicate", $data);
        return $this->api->factory($this, Job::class, $remote);
    }

    /**
     * @return Section
     */
    public function getDefaultSection()
    {
        return $this->defaultSection ?? $this->defaultSection = $this->getSections(1)[0];
    }

    /**
     * Iterates over sections.
     *
     * @see https://developers.asana.com/docs/get-sections-in-a-project
     *
     * @param int $limit
     * @return Traversable|Section[]
     */
    public function getIterator(int $limit = PHP_INT_MAX)
    {
        return $this->api->loadEach($this, Section::class, "{$this}/sections", ['limit' => $limit]);
    }

    /**
     * @return null
     */
    final protected function getParentNode()
    {
        return null;
    }

    /**
     * @param int $limit
     * @return Section[]
     */
    public function getSections(int $limit = PHP_INT_MAX)
    {
        return iterator_to_array($this->getIterator($limit));
    }

    /**
     * @return Status[]
     */
    public function getStatuses()
    {
        return $this->api->loadAll($this, Status::class, "{$this}/project_statuses");
    }

    /**
     * @return TaskCounts
     */
    public function getTaskCounts()
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
    public function getTasks(array $filter = Task::GET_INCOMPLETE)
    {
        $filter['project'] = $this->getGid();
        return $this->api->loadAll($this, Task::class, "tasks", $filter);
    }

    /**
     * @return string
     */
    final public function getUrl(): string
    {
        return "https://app.asana.com/0/{$this->getGid()}";
    }

    /**
     * @return ProjectWebhook[]
     */
    public function getWebhooks()
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
    public function newSection()
    {
        return $this->api->factory($this, Section::class, ['project' => $this]);
    }

    /**
     * Factory.
     *
     * @return Status
     */
    public function newStatus()
    {
        return $this->api->factory($this, Status::class);
    }

    /**
     * Factory.
     *
     * @return Task
     */
    public function newTask()
    {
        return $this->getDefaultSection()->newTask();
    }

    /**
     * Factory.
     *
     * @return ProjectWebhook
     */
    public function newWebhook()
    {
        /** @var ProjectWebhook $webhook */
        $webhook = $this->api->factory($this, ProjectWebhook::class);
        return $webhook->setResource($this);
    }

    /**
     * @param User $user
     * @return $this
     */
    public function removeFollower(User $user)
    {
        return $this->removeFollowers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function removeFollowers(array $users)
    {
        return $this->_removeWithPost("{$this}/removeFollowers", [
            'followers' => array_column($users, 'gid')
        ], 'followers', $users);
    }

    /**
     * @param User $user
     * @return $this
     */
    public function removeMember(User $user)
    {
        return $this->removeMembers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function removeMembers(array $users)
    {
        return $this->_removeWithPost("{$this}/removeMembers", [
            'members' => array_column($users, 'gid')
        ], 'members', $users);
    }
}