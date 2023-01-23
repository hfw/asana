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
class Change extends Data
{

    /**
     * Any payloads with types that are not present here will remain arrays.
     */
    protected const GRAPH = [
        Attachment::TYPE => Attachment::class,
        CustomField::TYPE => FieldEntry::class, // field entry. the custom field is in the event.
        Like::TYPE => Like::class,
        Project::TYPE => Project::class,
        Section::TYPE => Section::class,
        Story::TYPE => Story::class,
        Tag::TYPE => Tag::class,
        Task::TYPE => Task::class,
        User::TYPE => User::class,
    ];

    /**
     * @var string
     */
    protected string $key;

    /**
     * @param array $data
     * @return void
     */
    protected function _setData(array $data): void
    {
        $this->key = match ($data['action']) {
            Event::ACTION_ADDED => 'added_value',
            Event::ACTION_REMOVED => 'removed_value',
            Event::ACTION_CHANGED => 'new_value'
        };

        if ($payload = $data[$this->key] ?? null) {
            $type = $payload['resource_type'];
            if (isset(static::GRAPH[$type])) {
                $payload = $this->_hydrate(static::GRAPH[$type], $payload);
            }
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
     * @return null|array|Attachment|FieldEntry|Like|Project|Section|Story|Tag|Task|User
     */
    public function getPayload()
    {
        return $this->data[$this->key];
    }

    /**
     * @return bool
     */
    final public function hasPayload(): bool
    {
        return isset($this->data[$this->key]);
    }

    /**
     * @return bool
     */
    final public function wasAddition(): bool
    {
        return $this->getAction() === Event::ACTION_ADDED;
    }

    /**
     * @return bool
     */
    final public function wasRemoval(): bool
    {
        return $this->getAction() === Event::ACTION_REMOVED;
    }

    /**
     * @return bool
     */
    final public function wasValue(): bool
    {
        return $this->getAction() === Event::ACTION_CHANGED;
    }

}