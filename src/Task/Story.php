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
class Story extends AbstractEntity {

    use CrudTrait;

    const DIR = 'stories';
    const TYPE = 'story';

    const TYPE_ASSIGNED = 'assigned';
    const TYPE_COMMENT_ADDED = 'comment_added';
    const TYPE_COMMENT_LIKED = 'comment_liked';
    const TYPE_DUE_DATE_CHANGED = 'due_date_changed';
    const TYPE_LIKED = 'liked';
    const TYPE_TAGGED = 'added_to_tag';

    protected const MAP = [
        'created_by' => User::class,
        'likes' => [Like::class],
        'target' => Task::class
    ];

    public function __construct ($caller, array $data = []) {
        parent::__construct($caller, $data);
        $this->parent = $this->getTarget();
    }

    protected function _setData (array $data): void {
        // hearts were deprecated for likes
        unset($data['hearted'], $data['hearts'], $data['num_hearts']);

        parent::_setData($data);
    }

    /**
     * @return bool
     */
    final public function isAssignment (): bool {
        return $this->getResourceSubtype() === self::TYPE_ASSIGNED;
    }

    /**
     * @return bool
     */
    final public function isComment (): bool {
        return $this->getResourceSubtype() === self::TYPE_COMMENT_ADDED;
    }

    /**
     * @return bool
     */
    final public function isDueDate (): bool {
        return $this->getResourceSubtype() === self::TYPE_DUE_DATE_CHANGED;
    }

    /**
     * @return bool
     */
    final public function isEdited (): bool {
        return $this->_is('is_edited');
    }

    /**
     * @return bool
     */
    final public function isFromApi (): bool {
        return $this->getSource() === 'api';
    }

    /**
     * @return bool
     */
    final public function isFromWeb (): bool {
        return $this->getSource() === 'web';
    }

    /**
     * @return bool
     */
    final public function isLikedComment (): bool {
        return $this->getResourceSubtype() === self::TYPE_COMMENT_LIKED;
    }

    /**
     * @return bool
     */
    final public function isLikedTask (): bool {
        return $this->getResourceSubtype() === self::TYPE_LIKED;
    }

    /**
     * @return bool
     */
    final public function isPinned (): bool {
        return $this->_is('is_pinned');
    }

    /**
     * @return bool
     */
    final public function isTag (): bool {
        return $this->getResourceSubtype() === self::TYPE_TAGGED;
    }

    /**
     * @param bool $pinned
     * @return $this
     */
    final public function setPinned (bool $pinned) {
        return $this->_set('is_pinned', $pinned);
    }

}