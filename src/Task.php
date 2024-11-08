<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Base\AbstractEntity\DueTrait;
use Helix\Asana\Base\AbstractEntity\FollowersTrait;
use Helix\Asana\Base\AbstractEntity\LikesTrait;
use Helix\Asana\Base\AbstractEntity\SyncTrait;
use Helix\Asana\Base\AbstractEntity\UrlTrait;
use Helix\Asana\Base\DateTimeTrait;
use Helix\Asana\Project\Section;
use Helix\Asana\Task\Attachment;
use Helix\Asana\Task\ExternalData;
use Helix\Asana\Task\FieldEntries;
use Helix\Asana\Task\Like;
use Helix\Asana\Task\Membership;
use Helix\Asana\Task\Story;
use Helix\Asana\Webhook\TaskWebhook;

/**
 * A task.
 *
 * @see https://developers.asana.com/docs/asana-tasks
 * @see https://developers.asana.com/docs/task
 *
 * @see Workspace::newTask()
 * @see Project::newTask()
 * @see Section::newTask()
 *
 * @method $this                setResourceSubtype          (string $type)          @depends create-only
 * @method $this                setWorkspace                (Workspace $workspace)  @depends create-only
 * @method $this                setProjects                 (Project[] $projects)   @depends create-only
 *
 * @method null|User            getAssignee                 ()
 * @method string               getAssigneeStatus           ()
 * @method bool                 isCompleted                 ()
 * @method string               getCompletedAt              () RFC3339x
 * @method string               getCreatedAt                () RFC3339x
 * @method null|FieldEntries    getCustomFields             () Premium feature.
 * @method bool                 getIsRenderedAsSeparator    ()
 * @method Membership[]         getMemberships              ()
 * @method string               getModifiedAt               () RFC3339x
 * @method string               getName                     ()
 * @method string               getNotes                    ()
 * @method int                  getNumSubtasks              ()
 * @method null|Task            getParent                   ()
 * @method string               getResourceSubtype          ()
 * @method Tag[]                getTags                     ()
 * @method Workspace            getWorkspace                ()
 *
 * @method bool                 hasAssignee                 ()
 * @method bool                 hasCustomFields             () Premium feature.
 * @method bool                 hasMemberships              ()
 * @method bool                 hasName                     ()
 * @method bool                 hasNotes                    ()
 * @method bool                 hasParent                   ()
 * @method bool                 hasTags                     ()
 *
 * @method $this                setAssignee                 (?User $user)
 * @method $this                setAssigneeStatus           (string $status)
 * @method $this                setCompleted                (bool $completed)
 * @method $this                setHtmlNotes                (string $html) Must be wrapped in a `<body>` tag.
 * @method $this                setIsRenderedAsSeparator    (bool $flag)
 * @method $this                setName                     (string $name)
 * @method $this                setNotes                    (string $notes)
 *
 * @method Attachment[]         selectAttachments           (callable $filter) `fn( Attachment $attachment): bool`
 * @method Task[]               selectDependencies          (callable $filter) `fn( Task $dependency ): bool`
 * @method Task[]               selectDependents            (callable $filter) `fn( Task $dependent ): bool`
 * @method Story[]              selectComments              (callable $filter) `fn( Story $comment ): bool`
 * @method Membership[]         selectMemberships           (callable $filter) `fn( Membership $membership ): bool`
 * @method Project[]            selectProjects              (callable $filter) `fn( Project $project ): bool`
 * @method Story[]              selectStories               (callable $filter) `fn( Story $story ): bool`
 * @method Task[]               selectSubTasks              (callable $filter) `fn( Task $subtask ): bool`
 * @method Tag[]                selectTags                  (callable $filter) `fn( Tag $tag ): bool`
 *
 * @method bool ofMilestone ()
 */
class Task extends AbstractEntity
{

    use CrudTrait {
        create as private _create;
        update as private _update;
    }
    use DateTimeTrait {
        DateTimeTrait::_getDateTime as getCompletedAtDT;
        DateTimeTrait::_getDateTime as getCreatedAtDT;
        DateTimeTrait::_getDateTime as getModifiedAtDT;
    }
    use DueTrait;
    use FollowersTrait;
    use LikesTrait;
    use SyncTrait;
    use UrlTrait;

    final protected const DIR = 'tasks';
    final public const TYPE = 'task';

    final public const ASSIGN_INBOX = 'inbox';
    final public const ASSIGN_LATER = 'later';
    final public const ASSIGN_NEW = 'new';
    final public const ASSIGN_TODAY = 'today';
    final public const ASSIGN_UPCOMING = 'upcoming';

    final public const GET_INCOMPLETE = ['completed_since' => 'now'];

    protected const MAP = [
        'assignee' => User::class,
        'custom_fields' => FieldEntries::class,
        'external' => ExternalData::class, // opt-in
        'followers' => [User::class],
        'likes' => [Like::class],
        'memberships' => [Membership::class],
        'parent' => self::class,
        'tags' => [Tag::class],
        'workspace' => Workspace::class
    ];

    protected const OPT_FIELDS = [
        'memberships' => 'memberships.(project|section)'
    ];

    /**
     * @return null
     */
    final protected function _getParentNode()
    {
        return null;
    }

    /**
     * @return void
     */
    private function _onSave(): void
    {
        /** @var FieldEntries $fields */
        if ($fields = $this->data['custom_fields'] ?? null) {
            $fields->diff = [];
            foreach ($fields->getEntries() as $entry){
                $entry->diff = [];
            }
        }
        /** @var ExternalData $external */
        if ($external = $this->data['external'] ?? null) {
            $external->diff = [];
        }
    }

    /**
     * @param array $data
     * @return void
     */
    protected function _setData(array $data): void
    {
        // hearts were deprecated for likes.
        unset($data['hearted'], $data['hearts'], $data['num_hearts']);

        // redundant. memberships are used instead.
        unset($data['projects']);

        parent::_setData($data);
    }

    /**
     * Uploads a file attachment.
     *
     * @param string $file
     * @return Attachment
     */
    public function addAttachment(string $file): Attachment
    {
        $attachment = $this->api->factory(Attachment::class, $this, ['parent' => $this]);
        return $attachment->create($file);
    }

    /**
     * Premium feature.
     *
     * @param Task[] $tasks
     * @return $this
     */
    public function addDependencies(array $tasks): static
    {
        $this->api->post("{$this}/addDependencies", ['dependents' => array_column($tasks, 'gid')]);
        return $this;
    }

    /**
     * Premium feature.
     *
     * @param Task $task
     * @return $this
     */
    public function addDependency(Task $task): static
    {
        return $this->addDependencies([$task]);
    }

    /**
     * Premium feature.
     *
     * @param Task $task
     * @return $this
     */
    public function addDependent(Task $task): static
    {
        return $this->addDependents([$task]);
    }

    /**
     * Premium feature.
     *
     * @param Task[] $tasks
     * @return $this
     */
    public function addDependents(array $tasks): static
    {
        $this->api->post("{$this}/addDependents", ['dependents' => array_column($tasks, 'gid')]);
        return $this;
    }

    /**
     * Adds a tag.
     *
     * @see https://developers.asana.com/docs/add-a-tag-to-a-task
     *
     * @param Tag $tag
     * @return $this
     */
    public function addTag(Tag $tag): static
    {
        assert($tag->hasGid());
        return $this->_addWithPost("{$this}/addTag", [
            'tag' => $tag->getGid()
        ], 'tags', [$tag]);
    }

    /**
     * Adds the task to a project.
     *
     * @see https://developers.asana.com/docs/add-a-project-to-a-task
     *
     * @see Project::newTask()
     * @see Section::newTask()
     *
     * @param Project|Section $target
     * @return $this
     */
    public function addToProject($target): static
    {
        assert($target->hasGid());
        if ($target instanceof Project) {
            $target = $target->getDefaultSection();
        }
        $membership = $this->api->factory(Membership::class, $this)->setSection($target);
        return $this->_addWithPost("{$this}/addProject", $membership->toArray(), 'memberships', [$membership]);
    }

    /**
     * Adds the task to multiple projects.
     *
     * @param iterable|Project[]|Section[] $targets
     * @return $this
     */
    public function addToProjects(iterable $targets): static
    {
        foreach ($targets as $target) {
            $this->addToProject($target);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function create(): static
    {
        $this->_create();
        $this->_onSave();
        return $this;
    }

    /**
     * Duplicates the task.
     *
     * @see https://developers.asana.com/docs/duplicate-a-task
     *
     * @param string $name
     * @param string[] $include
     * @return Job
     */
    public function duplicate(string $name, array $include): Job
    {
        $remote = $this->api->post("{$this}/duplicate", [
            'name' => $name,
            'include' => array_values($include)
        ]);
        return $this->api->factory(Job::class, $this, $remote);
    }

    /**
     * Adds the API user as a follower.
     *
     * @return $this
     */
    public function follow(): static
    {
        return $this->addFollower($this->api->getMe());
    }

    /**
     * Attached files.
     *
     * @return Attachment[]
     */
    public function getAttachments(): array
    {
        return $this->api->loadAll($this, Attachment::class, "{$this}/attachments");
    }

    /**
     * @return Story[]
     */
    public function getComments(): array
    {
        return $this->selectStories(fn(Story $story) => $story->ofCommentAdded());
    }

    /**
     * Premium feature.
     *
     * @return Task[]
     */
    public function getDependencies(): array
    {
        return $this->api->loadAll($this, self::class, "{$this}/dependencies");
    }

    /**
     * Premium feature.
     *
     * @return Task[]
     */
    public function getDependents(): array
    {
        return $this->api->loadAll($this, self::class, "{$this}/dependents");
    }

    /**
     * A proxy to the task's "external data".
     *
     * > :info:
     * > This always returns an instance, regardless of whether the task on Asana actually has external data.
     * >
     * > Asana will delete the external data object if it's emptied,
     * > and fetching it via `GET` will then return `null`, so we coalesce.
     *
     * @return ExternalData
     */
    public function getExternal(): ExternalData
    {
        return $this->_get('external') ?? $this->data['external'] = $this->api->factory(ExternalData::class, $this);
    }

    /**
     * @return Project[]
     */
    public function getProjects(): array
    {
        return array_column($this->getMemberships(), 'project');
    }

    /**
     * @return Section[]
     */
    public function getSections(): array
    {
        return array_column($this->getMemberships(), 'section');
    }

    /**
     * @return Story[]
     */
    public function getStories(): array
    {
        return $this->api->loadAll($this, Story::class, "{$this}/stories");
    }

    /**
     * @return Task[]
     */
    public function getSubTasks(): array
    {
        return $this->api->loadAll($this, self::class, "{$this}/subtasks");
    }

    /**
     * @return TaskWebhook[]
     */
    public function getWebhooks(): array
    {
        return $this->api->loadAll($this, TaskWebhook::class, 'webhooks', [
            'workspace' => $this->getWorkspace()->getGid(),
            'resource' => $this->getGid()
        ]);
    }

    /**
     * Factory.
     *
     * @return Story
     */
    public function newComment(): Story
    {
        return $this->api->factory(Story::class, $this, [
            'resource_subtype' => 'comment_added',
            'target' => $this
        ]);
    }

    /**
     * Factory.
     *
     * @return Task
     */
    public function newSubTask(): Task
    {
        return $this->api->factory(self::class, $this)->setParent($this);
    }

    /**
     * Factory.
     *
     * @return TaskWebhook
     */
    public function newWebhook(): TaskWebhook
    {
        return $this->api->factory(TaskWebhook::class, $this)->setResource($this);
    }

    /**
     * Premium feature.
     *
     * @param Task[] $tasks
     * @return $this
     */
    public function removeDependencies(array $tasks): static
    {
        $this->api->post("{$this}/removeDependencies", ['dependencies' => array_column($tasks, 'gid')]);
        return $this;
    }

    /**
     * Premium feature.
     *
     * @param Task $task
     * @return $this
     */
    public function removeDependency(Task $task): static
    {
        return $this->removeDependencies([$task]);
    }

    /**
     * Premium feature.
     *
     * @param Task $task
     * @return $this
     */
    public function removeDependent(Task $task): static
    {
        return $this->removeDependents([$task]);
    }

    /**
     * Premium feature.
     *
     * @param Task[] $tasks
     * @return $this
     */
    public function removeDependents(array $tasks): static
    {
        $this->api->post("{$this}/removeDependents", ['dependents' => array_column($tasks, 'gid')]);
        return $this;
    }

    /**
     * Removes the task from a project.
     *
     * @see https://developers.asana.com/docs/remove-a-project-from-a-task
     *
     * @param Project $project
     * @return $this
     */
    public function removeFromProject(Project $project): static
    {
        return $this->_removeWithPost("{$this}/removeProject", ['project' => $project->getGid()], 'memberships',
            fn(Membership $membership) => $membership->getProject()->getGid() !== $project->getGid()
        );
    }

    /**
     * Removes the task from multiple projects.
     *
     * @param iterable|Project[] $projects
     * @return $this
     */
    public function removeFromProjects(iterable $projects): static
    {
        foreach ($projects as $project) {
            $this->removeFromProject($project);
        }
        return $this;
    }

    /**
     * Removes a tag.
     *
     * @see https://developers.asana.com/docs/remove-a-tag-from-a-task
     *
     * @param Tag $tag
     * @return $this
     */
    public function removeTag(Tag $tag): static
    {
        return $this->_removeWithPost("{$this}/removeTag", [
            'tag' => $tag->getGid()
        ], 'tags', [$tag]);
    }

    /**
     * Makes the task a subtask of another.
     *
     * @see https://developers.asana.com/docs/set-the-parent-of-a-task
     * @param null|Task $parent
     * @return $this
     */
    final public function setParent(?Task $parent): static
    {
        assert(!$parent or $parent->hasGid());
        return $this->_setWithPost("{$this}/setParent", [
            'parent' => $parent
        ], 'parent', $parent);
    }

    /**
     * Removes the API user as a follower.
     *
     * @return $this
     */
    public function unfollow(): static
    {
        return $this->removeFollower($this->api->getMe());
    }

    /**
     * @return $this
     */
    public function update(): static
    {
        $this->_update();
        $this->_onSave();
        return $this;
    }

}
