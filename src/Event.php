<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\Data;
use Helix\Asana\Event\Change;
use Helix\Asana\Project\Section;
use Helix\Asana\Task\Attachment;
use Helix\Asana\Task\Like;
use Helix\Asana\Task\Story;

/**
 * An event obtained via sync token or delivered to you via webhook.
 *
 * > :warning:
 * > Asana has a "feature" that squashes "duplicate" events into the oldest date,
 * > as well as removing events inbetween, effectively rewriting history.
 * >
 * > As such, any particular sequence of events should not be greatly relied upon for continuity.
 * > Cached or already-pooled entities will be stale as well.
 * >
 * > You should reload the resource and go off of that data instead.
 *
 * @see https://developers.asana.com/docs/get-events-on-a-resource
 * @see https://developers.asana.com/docs/event
 *
 * @see Project::getEvents()
 * @see Task::getEvents()
 * @see Api::getWebhookEvent()
 * @see AbstractEntity::reload()
 *
 * @method string       getAction       () The action-verb for the event.
 * @method null|Change  getChange       () The change made on the resource.
 * @method string       getCreatedAt    () RFC3339x
 * @method null|User    getUser         () The initiator, if there was one.
 *
 * @method bool         hasChange       () False if the event was relational.
 * @method bool         hasParent       () True if the event was relational.
 * @method bool         hasUser         () When false, Asana initiated the event. ("system")
 */
class Event extends Data {

    const ACTION_CHANGED = 'changed';       // no parent
    const ACTION_ADDED = 'added';           // relational, no change
    const ACTION_REMOVED = 'removed';       // relational, no change
    const ACTION_DELETED = 'deleted';       // no parent or change
    const ACTION_UNDELETED = 'undeleted';   // no parent or change

    const GRAPH = [
        User::TYPE => User::class,
        Project::TYPE => Project::class,
        Section::TYPE => Section::class,
        Task::TYPE => Task::class,
        CustomField::TYPE => CustomField::class, // field!
        Attachment::TYPE => Attachment::class,
        Tag::TYPE => Tag::class,
        Story::TYPE => Story::class,
        Like::TYPE => Like::class
    ];

    protected const MAP = [
        'change' => Change::class,
        'user' => User::class
    ];

    protected function _setData (array $data): void {
        if (isset($data['parent'])) {
            $type = $data['parent']['resource_type'];
            $data['parent'] = $this->_hydrate(static::GRAPH[$type], $data['parent']);
        }

        $type = $data['resource']['resource_type'];
        $data['resource'] = $this->_hydrate(static::GRAPH[$type], $data['resource']);

        parent::_setData($data);
    }

    /**
     * The parent resource, if the event was relational.
     *
     * @return null|Project|Section|Task
     */
    public function getParent () {
        return $this->data['parent'] ?? null;
    }

    /**
     * The relational child, or the entity that was changed.
     *
     * @return User|Project|Section|Task|CustomField|Attachment|Story|Like
     */
    public function getResource () {
        return $this->data['resource'];
    }

    /**
     * @return bool
     */
    final public function wasAddition (): bool {
        return $this->getAction() === self::ACTION_ADDED;
    }

    /**
     * @return bool
     */
    final public function wasChange (): bool {
        return $this->getAction() === self::ACTION_CHANGED;
    }

    /**
     * @return bool
     */
    final public function wasDeletion (): bool {
        return $this->getAction() === self::ACTION_DELETED;
    }

    /**
     * @return bool
     */
    final public function wasRemoval (): bool {
        return $this->getAction() === self::ACTION_REMOVED;
    }

    /**
     * @return bool
     */
    final public function wasUndeletion (): bool {
        return $this->getAction() === self::ACTION_UNDELETED;
    }
}