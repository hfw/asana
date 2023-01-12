<?php

namespace Helix\Asana\Task;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
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
 * @method bool         isLiked             () Whether the story is liked by the API user.
 * @method Like[]       getLikes            () Other people's likes.
 * @method int          getNumLikes         ()
 * @method string       getResourceSubtype  () See the subtype constants.
 * @method string       getSource           () `web|api`
 * @method Task         getTarget           ()
 * @method string       getText             ()
 *
 * @method $this        setLiked            (bool $liked)
 *
 * @method Like[]       selectLikes         (callable $filter) `fn( Like $like ): bool`
 */
class Story extends AbstractEntity
{

    use CrudTrait;

    final protected const DIR = 'stories';
    final public const TYPE = 'story';

    final public const TYPE_ASSIGNED = 'assigned';
    final public const TYPE_COMMENT_ADDED = 'comment_added';
    final public const TYPE_COMMENT_LIKED = 'comment_liked';
    final public const TYPE_DUE_DATE_CHANGED = 'due_date_changed';
    final public const TYPE_ENUM_CUSTOM_FIELD_CHANGED = 'enum_custom_field_changed';
    final public const TYPE_FOLLOWER_ADDED = 'follower_added';
    final public const TYPE_LIKED = 'liked';
    final public const TYPE_MARKED_COMPLETE = 'marked_complete';
    final public const TYPE_MARKED_INCOMPLETE = 'marked_incomplete';
    final public const TYPE_NUMBER_CUSTOM_FIELD_CHANGED = 'number_custom_field_changed';
    final public const TYPE_TAGGED = 'added_to_tag';

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
    final public function isAssignment(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_ASSIGNED;
    }

    /**
     * @return bool
     */
    final public function isComment(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_COMMENT_ADDED;
    }

    /**
     * @return bool
     */
    final public function isComplete(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_MARKED_COMPLETE;
    }

    /**
     * @return bool
     */
    final public function isDueDate(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_DUE_DATE_CHANGED;
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
    final public function isEnum(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_ENUM_CUSTOM_FIELD_CHANGED;
    }

    /**
     * @return bool
     */
    final public function isFollower(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_FOLLOWER_ADDED;
    }

    /**
     * @return bool
     */
    final public function isFromApi(): bool
    {
        return $this->getSource() === 'api';
    }

    /**
     * @return bool
     */
    final public function isFromWeb(): bool
    {
        return $this->getSource() === 'web';
    }

    /**
     * @return bool
     */
    final public function isIncomplete(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_MARKED_INCOMPLETE;
    }

    /**
     * @return bool
     */
    final public function isLikedComment(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_COMMENT_LIKED;
    }

    /**
     * @return bool
     */
    final public function isLikedTask(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_LIKED;
    }

    /**
     * @return bool
     */
    final public function isNumber(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_NUMBER_CUSTOM_FIELD_CHANGED;
    }

    /**
     * @return bool
     */
    final public function isPinned(): bool
    {
        return $this->_is('is_pinned');
    }

    /**
     * @return bool
     */
    final public function isTag(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_TAGGED;
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