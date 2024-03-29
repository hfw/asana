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
     * > :warning:
     * > Asana throws `400` when events are read from unknown/deleted entities.
     *
     * @see https://developers.asana.com/docs/get-events-on-a-resource
     *
     * @param null|string $token Updated to the new token.
     * @return Event[] Empty if the given token is `NULL`
     * @throws AsanaError The given (non-null) token expired, or general API error.
     */
    public function getEvents(?string &$token): array
    {
        try {
            /** @var array $remote never null (404) */
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
                    // The caller gave a null token, so we don't need to rethrow.
                    $token = $remote['sync'];
                    return [];
                }
                // Token expired. Update and rethrow.
                $token = $remote['sync'];
            }
            throw $error;
        }
        $token = $remote['sync'];
        $events = array_map(
            fn(array $each) => $this->api->factory(Event::class, $this, $each),
            $remote['data'] ?? []
        );
        usort($events, fn(Event $a, Event $b) => $a->getCreatedAt() <=> $b->getCreatedAt());
        return $events;
    }
}