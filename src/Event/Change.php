<?php

namespace Helix\Asana\Event;

use Helix\Asana\Base\Data;
use Helix\Asana\CustomField;
use Helix\Asana\Event;
use Helix\Asana\Project;
use Helix\Asana\Project\Section;
use Helix\Asana\Tag;
use Helix\Asana\Task;
use Helix\Asana\Task\Attachment;
use Helix\Asana\Task\FieldEntry;
use Helix\Asana\Task\Like;
use Helix\Asana\Task\Story;
use Helix\Asana\User;

/**
 * The change on an event's resource.
 *
 * @see https://developers.asana.com/docs/event
 *
 * @method string getAction () The change's action-verb.
 * @method string getField  ()
 */
class Change extends Data {

    const GRAPH = [
        User::TYPE => User::class,
        Project::TYPE => Project::class,
        Section::TYPE => Section::class,
        Task::TYPE => Task::class,
        CustomField::TYPE => FieldEntry::class, // entry!
        Tag::TYPE => Tag::class,
        Attachment::TYPE => Attachment::class,
        Story::TYPE => Story::class,
        Like::TYPE => Like::class
    ];

    /**
     * @var string
     */
    protected $key;

    protected function _setData (array $data): void {
        $this->key = [
            Event::ACTION_ADDED => 'added_value',
            Event::ACTION_REMOVED => 'removed_value',
            Event::ACTION_CHANGED => 'new_value'
        ][$data['action']];

        if ($payload = $data[$this->key] ?? null) {
            $payload = $this->_hydrate(self::GRAPH[$payload['resource_type']] ?? Data::class, $payload);
        }

        $this->data = [
            'action' => $data['action'],
            'field' => $data['field'],
            $this->key => $payload
        ];
    }

    /**
     * The contents of the change.
     *
     * > :warning:
     * > This is `null` for changes to scalar fields.
     * > You should reload the event's resource and check it.
     *
     * @return null|User|Project|Section|Task|FieldEntry|Attachment|Story|Like
     */
    public function getPayload () {
        return $this->data[$this->key];
    }

    /**
     * @return bool
     */
    final public function hasPayload (): bool {
        return isset($this->data[$this->key]);
    }

    /**
     * @return bool
     */
    final public function wasAddition (): bool {
        return $this->getAction() === Event::ACTION_ADDED;
    }

    /**
     * @return bool
     */
    final public function wasRemoval (): bool {
        return $this->getAction() === Event::ACTION_REMOVED;
    }

    /**
     * @return bool
     */
    final public function wasValue (): bool {
        return $this->getAction() === Event::ACTION_CHANGED;
    }

}