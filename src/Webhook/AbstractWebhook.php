<?php

namespace Helix\Asana\Webhook;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CreateTrait;
use Helix\Asana\Base\AbstractEntity\DeleteTrait;
use Helix\Asana\Base\DateTimeTrait;

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
abstract class AbstractWebhook extends AbstractEntity
{

    use CreateTrait;
    use DateTimeTrait {
        _getDateTime as getCreatedAtDT;
        _getDateTime as getLastFailureAtDT;
        _getDateTime as getLastSuccessAtDT;
    }
    use DeleteTrait;

    // no need for $parent, new webhooks are posted here.
    final protected const DIR = 'webhooks';

    /**
     * @return null
     */
    final protected function _getParentNode()
    {
        return null;
    }

}