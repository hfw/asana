<?php

namespace Helix\Asana\Webhook;

use Helix\Asana\Project;

/**
 * A project webhook.
 *
 * @see https://developers.asana.com/docs/asana-webhooks
 * @see https://developers.asana.com/docs/webhook
 *
 * @see Project::newWebhook()
 *
 * @method $this    setResource (Project $project) @depends create-only
 * @method string   setTarget   (string $url) @depends create-only
 *
 * @method Project  getResource ()
 */
class ProjectWebhook extends AbstractWebhook {

    const TYPE = 'project_webhook';

    protected const MAP = [
        'resource' => Project::class
    ];
}