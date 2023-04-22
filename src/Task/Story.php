<?php

namespace Helix\Asana\Task;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Base\AbstractEntity\LikesTrait;
use Helix\Asana\Base\DateTimeTrait;
use Helix\Asana\Task;
use Helix\Asana\User;

/**
 * A task story.
 *
 * @see https://developers.asana.com/docs/asana-stories
 * @see https://developers.asana.com/docs/story
 *
 * @see Task::newComment()
 *
 * @method $this        setText             (string $text) @depends create-only, comments only
 *
 * @method string       getCreatedAt        () RFC3339x
 * @method null|User    getCreatedBy        () This will be `null` if Asana produced the story.
 * @method int          getNumLikes         ()
 * @method string       getResourceSubtype  ()
 * @method string       getSource           () `web|api`
 * @method Task         getTarget           ()
 * @method string       getText             ()
 *
 * @method bool ofAllDependenciesMarkedComplete ()
 * @method bool ofAddedToProject                ()
 * @method bool ofAddedToTag                    ()
 * @method bool ofAssigned                      ()
 * @method bool ofAttachmentAdded               ()
 * @method bool ofCommentAdded                  ()
 * @method bool ofCommentLiked                  ()
 * @method bool ofEnumCustomFieldChanged        ()
 * @method bool ofDependencyAdded               () Task has a new dependency.
 * @method bool ofDependencyMarkedComplete      ()
 * @method bool ofDependencyMarkedIncomplete    ()
 * @method bool ofDependentAdded                () Task became a dependency.
 * @method bool ofDueDateChanged                ()
 * @method bool ofDueToday                      ()
 * @method bool ofDuplicated                    () Task was created via duplication.
 * @method bool ofFollowerAdded                 ()
 * @method bool ofLiked                         () Task itself was liked.
 * @method bool ofMarkedComplete                ()
 * @method bool ofMarkedIncomplete              ()
 * @method bool ofMentioned                     () Task was mentioned in another task.
 * @method bool ofNameChanged                   ()
 * @method bool ofNotesChanged                  ()
 * @method bool ofNumberCustomFieldChanged      ()
 * @method bool ofRemovedFromProject            ()
 * @method bool ofSectionChanged                ()
 * @method bool ofUnassigned                    ()
 * @method bool ofRemovedFromTag                ()
 * @method bool ofTextCustomFieldChanged        ()
 */
class Story extends AbstractEntity
{

    use CrudTrait;
    use DateTimeTrait {
        _getDateTime as getCreatedAtDT;
        _getDateTime as getDueChangeDT;
    }
    use LikesTrait;

    final protected const DIR = 'stories';
    final public const TYPE = 'story';

    protected const MAP = [
        'created_by' => User::class,
        'likes' => [Like::class],
        'target' => Task::class
    ];

    /**
     * @return Task
     */
    final protected function _getParentNode(): Task
    {
        return $this->getTarget();
    }

    /**
     * @param array $data
     * @return void
     */
    protected function _setData(array $data): void
    {
        // hearts were deprecated for likes
        unset($data['hearted'], $data['hearts'], $data['num_hearts']);

        parent::_setData($data);
    }

    /**
     * Short-month and day, including year if different than the current one.
     *
     * e.g. "Jan 1" (current year), or "Jan 1, 20xx" (different year)
     *
     * This is used by {@link getDueChangeDT()}, which will be in the runtime's timezone.
     *
     * @return null|string
     */
    final public function getDueChange(): ?string
    {
        if (preg_match('/ changed the due date to (?<due>.+)$/', $this->getText(), $change)) {
            return $change['due'];
        }
        return null;
    }

    /**
     * Key-value pair of custom field change.
     *
     * The key is the field name, not its GID.
     *
     * @return array<string,null|string>
     */
    public function getKeyValue(): array
    {
        if (preg_match('/ changed (?<key>.*?) from ".*?" to "(?<value>.*)"$/', $this->getText(), $edit)) {
            return [$edit['key'] => $edit['value']];
        }
        if (preg_match('/ changed (?<key>.*?) to "(?<value>.*)"$/', $this->getText(), $init)) {
            return [$init['key'] => $init['value']];
        }
        if (preg_match('/ cleared (?<key>.*)$/', $this->getText(), $clear)) {
            return [$clear['key'] => null];
        }
        return [];
    }

    /**
     * @return bool
     */
    final public function isEdited(): bool
    {
        return $this->_is('is_edited');
    }

    /**
     * @return bool
     */
    final public function isPinned(): bool
    {
        return $this->_is('is_pinned');
    }

    /**
     * @param bool $pinned
     * @return $this
     */
    final public function setPinned(bool $pinned): static
    {
        return $this->_set('is_pinned', $pinned);
    }

}
