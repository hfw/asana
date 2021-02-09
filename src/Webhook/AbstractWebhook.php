<?php

namespace Helix\Asana\Webhook;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CreateTrait;
use Helix\Asana\Base\AbstractEntity\DeleteTrait;
use Helix\Asana\Base\AbstractEntity\ImmutableInterface;

/**
 * A webhook.
 *
 * @immutable Webhooks can only be created and deleted.
 *
 * @see https://developers.asana.com/docs/asana-webhooks
 * @see https://developers.asana.com/docs/webhook
 *
 * @method bool     isActive                ()
 * @method string   getCreatedAt            () RFC3339x
 * @method string   getLastFailureAt        () RFC3339x
 * @method string   getLastFailureContent   ()
 * @method string   getLastSuccessAt        () RFC3339x
 * @method string   getTarget               ()
 */
abstract class AbstractWebhook extends AbstractEntity implements ImmutableInterface {

    use CreateTrait;
    use DeleteTrait;

    // no need for $parent, new webhooks are posted here.
    const DIR = 'webhooks';

}