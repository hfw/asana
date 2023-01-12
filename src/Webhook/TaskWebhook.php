<?php

namespace Helix\Asana\Webhook;

use Helix\Asana\Task;

/**
 * A task webhook.
 *
 * @see https://developers.asana.com/docs/asana-webhooks
 * @see https://developers.asana.com/docs/webhook
 *
 * @see Task::newWebhook()
 *
 * @method $this    setResource (Task $task) @depends create-only
 * @method string   setTarget   (string $url) @depends create-only
 *
 * @method Task     getResource ()
 */
class TaskWebhook extends AbstractWebhook
{

    final public const TYPE = 'task_webhook';

    protected const MAP = [
        'resource' => Task::class
    ];
}