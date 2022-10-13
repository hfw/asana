<?php

namespace Helix\Asana\Base\AbstractEntity;

use Helix\Asana\Api\AsanaError;
use Helix\Asana\Event;

/**
 * Adds event syncing to entities.
 */
trait SyncTrait
{

    /**
     * Polls for new events.
     *
     * > :info:
     * > If the given token is `null` then this method catches Asana's `412` and returns an empty array.
     *
     * @see https://developers.asana.com/docs/get-events-on-a-resource
     *
     * @param null|string $token Updated to the new token.
     * @return Event[]
     */
    public function getEvents(?string &$token)
    {
        try {
            /** @var array $remote Asana throws 400 for missing entities. */
            $remote = $this->api->call('GET', 'events?' . http_build_query([
                    'resource' => $this->getGid(),
                    'sync' => $token,
                    'opt_expand' => 'this'
                ]));
        } catch (AsanaError $error) {
            if ($error->is(412)) {
                $remote = $error->asResponse();
                if (!isset($token)) {
                    // Asana says: "The response will be the same as for an expired sync token."
                    // The caller knowingly gave a null token, so we don't need to rethrow.
                    $token = $remote['sync'];
                    return [];
                }
                // Token expired. Update and rethrow.
                $token = $remote['sync'];
            }
            throw $error;
        }
        $token = $remote['sync'];
        $events = array_map(function (array $each) {
            return $this->api->factory($this, Event::class, $each);
        }, $remote['data'] ?? []);
        usort($events, function (Event $a, Event $b) {
            return $a->getCreatedAt() <=> $b->getCreatedAt();
        });
        return $events;
    }
}