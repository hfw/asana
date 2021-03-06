<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Base\AbstractEntity\DateTrait;
use Helix\Asana\Base\AbstractEntity\PostMutatorTrait;
use Helix\Asana\Base\AbstractEntity\SyncTrait;
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
 *
 * @method null|User            getAssignee                 ()
 * @method string               getAssigneeStatus           ()
 * @method bool                 isCompleted                 ()
 * @method string               getCompletedAt              () RFC3339x
 * @method string               getCreatedAt                () RFC3339x
 * @method null|FieldEntries    getCustomFields             () Premium feature.
 * @method User[]               getFollowers                ()
 * @method bool                 getIsRenderedAsSeparator    ()
 * @method bool                 isLiked                     () Whether you like the task.
 * @method Like[]               getLikes                    ()
 * @method Membership[]         getMemberships              ()
 * @method string               getModifiedAt               () RFC3339x
 * @method string               getName                     ()
 * @method string               getNotes                    ()
 * @method int                  getNumLikes                 ()
 * @method int                  getNumSubtasks              ()
 * @method null|Task            getParent                   ()
 * @method string               getResourceSubtype          ()
 * @method Tag[]                getTags                     ()
 * @method Workspace            getWorkspace                ()
 *
 * @method bool                 hasAssignee                 ()
 * @method bool                 hasCustomFields             () Premium feature.
 * @method bool                 hasFollowers                ()
 * @method bool                 hasLikes                    ()
 * @method bool                 hasMemberships              ()
 * @method bool                 hasName                     ()
 * @method bool                 hasNotes                    ()
 * @method bool                 hasParent                   ()
 * @method bool                 hasTags                     ()
 *
 * @method $this                setAssignee                 (?User $user)
 * @method $this                setAssigneeStatus           (string $status)
 * @method $this                setCompleted                (bool $completed)
 * @method $this                setIsRenderedAsSeparator    (bool $flag)
 * @method $this                setLiked                    (bool $liked) Like or unlike the task.
 * @method $this                setName                     (string $name)
 * @method $this                setNotes                    (string $notes)
 *
 * @method Attachment[]         selectAttachments           (callable $filter) `fn( Attachment $attachment): bool`
 * @method Task[]               selectDependencies          (callable $filter) `fn( Task $dependency ): bool`
 * @method Task[]               selectDependents            (callable $filter) `fn( Task $dependent ): bool`
 * @method User[]               selectFollowers             (callable $filter) `fn( User $user ): bool`
 * @method Story[]              selectComments              (callable $filter) `fn( Story $comment ): bool`
 * @method Like[]               selectLikes                 (callable $filter) `fn( Like $like ): bool`
 * @method Membership[]         selectMemberships           (callable $filter) `fn( Membership $membership ): bool`
 * @method Project[]            selectProjects              (callable $filter) `fn( Project $project ): bool`
 * @method Story[]              selectStories               (callable $filter) `fn( Story $story ): bool`
 * @method Task[]               selectSubTasks              (callable $filter) `fn( Task $subtask ): bool`
 * @method Tag[]                selectTags                  (callable $filter) `fn( Tag $tag ): bool`
 */
class Task extends AbstractEntity {

    use CrudTrait {
        create as private _create;
        update as private _update;
    }

    use DateTrait;
    use PostMutatorTrait;
    use SyncTrait;

    const DIR = 'tasks';
    const TYPE = 'task';

    const TYPE_DEFAULT = 'default_task';
    const TYPE_MILESTONE = 'milestone';

    const ASSIGN_INBOX = 'inbox';
    const ASSIGN_LATER = 'later';
    const ASSIGN_NEW = 'new';
    const ASSIGN_TODAY = 'today';
    const ASSIGN_UPCOMING = 'upcoming';

    const GET_INCOMPLETE = ['completed_since' => 'now'];

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

    const OPT_FIELDS = [
        'memberships' => 'memberships.(project|section)'
    ];

    private function _onSave (): void {
        /** @var FieldEntries $fields */
        if ($fields = $this->data['custom_fields'] ?? null) {
            $fields->__unset(true);
        }
        /** @var ExternalData $external */
        if ($external = $this->data['external'] ?? null) {
            $external->diff = [];
        }
    }

    protected function _setData (array $data): void {
        // hearts were deprecated for likes.
        unset($data['hearted'], $data['hearts'], $data['num_hearts']);

        // redundant. memberships are used instead.
        unset($data['projects']);

        // time-based deadlines are a little passive-aggressive, don't you think?
        unset($data['due_at']);

        parent::_setData($data);
    }

    /**
     * Uploads a file attachment.
     *
     * @param string $file
     * @return Attachment
     */
    public function addAttachment (string $file) {
        /** @var Attachment $attachment */
        $attachment = $this->api->factory($this, Attachment::class, ['parent' => $this]);
        return $attachment->create($file);
    }

    /**
     * Premium feature.
     *
     * @param Task[] $tasks
     * @return $this
     */
    public function addDependencies (array $tasks) {
        $this->api->post("{$this}/addDependencies", ['dependents' => array_column($tasks, 'gid')]);
        return $this;
    }

    /**
     * Premium feature.
     *
     * @param Task $task
     * @return $this
     */
    public function addDependency (Task $task) {
        return $this->addDependencies([$task]);
    }

    /**
     * Premium feature.
     *
     * @param Task $task
     * @return $this
     */
    public function addDependent (Task $task) {
        return $this->addDependents([$task]);
    }

    /**
     * Premium feature.
     *
     * @param Task[] $tasks
     * @return $this
     */
    public function addDependents (array $tasks) {
        $this->api->post("{$this}/addDependents", ['dependents' => array_column($tasks, 'gid')]);
        return $this;
    }

    /**
     * Adds a follower.
     *
     * @param User $user
     * @return $this
     */
    public function addFollower (User $user) {
        return $this->addFollowers([$user]);
    }

    /**
     * Adds followers.
     *
     * @see https://developers.asana.com/docs/add-followers-to-a-task
     *
     * @param User[] $users
     * @return $this
     */
    public function addFollowers (array $users) {
        return $this->_addWithPost("{$this}/addFollowers", [
            'followers' => array_column($users, 'gid')
        ], 'followers', $users);
    }

    /**
     * Adds a tag.
     *
     * @see https://developers.asana.com/docs/add-a-tag-to-a-task
     *
     * @param Tag $tag
     * @return $this
     */
    public function addTag (Tag $tag) {
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
    public function addToProject ($target) {
        assert($target->hasGid());
        if ($target instanceof Project) {
            $target = $target->getDefaultSection();
        }
        /** @var Membership $membership */
        $membership = $this->api->factory($this, Membership::class);
        $membership->setSection($target);
        return $this->_addWithPost("{$this}/addProject", $membership->toArray(), 'memberships', [$membership]);
    }

    /**
     * @return $this
     */
    public function create () {
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
    public function duplicate (string $name, array $include) {
        /** @var array $remote */
        $remote = $this->api->post("{$this}/duplicate", [
            'name' => $name,
            'include' => array_values($include)
        ]);
        return $this->api->factory($this, Job::class, $remote);
    }

    /**
     * Attached files.
     *
     * @return Attachment[]
     */
    public function getAttachments () {
        return $this->api->loadAll($this, Attachment::class, "{$this}/attachments");
    }

    /**
     * @return Story[]
     */
    public function getComments () {
        return $this->selectStories(function(Story $story) {
            return $story->isComment();
        });
    }

    /**
     * Premium feature.
     *
     * @return Task[]
     */
    public function getDependencies () {
        return $this->api->loadAll($this, self::class, "{$this}/dependencies");
    }

    /**
     * Premium feature.
     *
     * @return Task[]
     */
    public function getDependents () {
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
    public function getExternal () {
        return $this->_get('external') ?? $this->data['external'] = $this->api->factory($this, ExternalData::class);
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
        return array_column($this->getMemberships(), 'project');
    }

    /**
     * @return Section[]
     */
    public function getSections () {
        return array_column($this->getMemberships(), 'section');
    }

    /**
     * @return Story[]
     */
    public function getStories () {
        return $this->api->loadAll($this, Story::class, "{$this}/stories");
    }

    /**
     * @return Task[]
     */
    public function getSubTasks () {
        return $this->api->loadAll($this, self::class, "{$this}/subtasks");
    }

    /**
     * @return string
     */
    final public function getUrl (): string {
        return "https://app.asana.com/0/0/{$this->getGid()}";
    }

    /**
     * @return TaskWebhook[]
     */
    public function getWebhooks () {
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
    public function newComment () {
        return $this->api->factory($this, Story::class, [
            'resource_subtype' => Story::TYPE_COMMENT_ADDED,
            'target' => $this
        ]);
    }

    /**
     * Factory.
     *
     * @return Task
     */
    public function newSubTask () {
        /** @var Task $sub */
        $sub = $this->api->factory($this, self::class);
        return $sub->setParent($this);
    }

    /**
     * Factory.
     *
     * @return TaskWebhook
     */
    public function newWebhook () {
        /** @var TaskWebhook $webhook */
        $webhook = $this->api->factory($this, TaskWebhook::class);
        return $webhook->setResource($this);
    }

    /**
     * Premium feature.
     *
     * @param Task[] $tasks
     * @return $this
     */
    public function removeDependencies (array $tasks) {
        $this->api->post("{$this}/removeDependencies", ['dependencies' => array_column($tasks, 'gid')]);
        return $this;
    }

    /**
     * Premium feature.
     *
     * @param Task $task
     * @return $this
     */
    public function removeDependency (Task $task) {
        return $this->removeDependencies([$task]);
    }

    /**
     * Premium feature.
     *
     * @param Task $task
     * @return $this
     */
    public function removeDependent (Task $task) {
        return $this->removeDependents([$task]);
    }

    /**
     * Premium feature.
     *
     * @param Task[] $tasks
     * @return $this
     */
    public function removeDependents (array $tasks) {
        $this->api->post("{$this}/removeDependents", ['dependents' => array_column($tasks, 'gid')]);
        return $this;
    }

    /**
     * Removes a follower.
     *
     * @param User $user
     * @return $this
     */
    public function removeFollower (User $user) {
        return $this->removeFollowers([$user]);
    }

    /**
     * Removes followers.
     *
     * @see https://developers.asana.com/docs/remove-followers-from-a-task
     *
     * @param User[] $users
     * @return $this
     */
    public function removeFollowers (array $users) {
        return $this->_removeWithPost("{$this}/removeFollowers", [
            'followers' => array_column($users, 'gid')
        ], 'followers', $users);
    }

    /**
     * Removes the task from a project.
     *
     * @see https://developers.asana.com/docs/remove-a-project-from-a-task
     *
     * @param Project $project
     * @return $this
     */
    public function removeFromProject (Project $project) {
        return $this->_removeWithPost("{$this}/removeProject", [
            'project' => $project->getGid()
        ], 'memberships', function(Membership $membership) use ($project) {
            return $membership->getProject()->getGid() !== $project->getGid();
        });
    }

    /**
     * Removes a tag.
     *
     * @see https://developers.asana.com/docs/remove-a-tag-from-a-task
     *
     * @param Tag $tag
     * @return $this
     */
    public function removeTag (Tag $tag) {
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
    final public function setParent (?Task $parent) {
        assert(!$parent or $parent->hasGid());
        return $this->_setWithPost("{$this}/setParent", [
            'parent' => $parent
        ], 'parent', $parent);
    }

    /**
     * @return $this
     */
    public function update () {
        $this->_update();
        $this->_onSave();
        return $this;
    }

}