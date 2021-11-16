<?php

namespace Helix\Asana\Api\Laravel\Facade;

use Helix\Asana\Api;
use Helix\Asana\Api\Laravel\AsanaServiceProvider;
use Helix\Asana\CustomField;
use Helix\Asana\Event;
use Helix\Asana\Job;
use Helix\Asana\OrganizationExport;
use Helix\Asana\Portfolio;
use Helix\Asana\Project;
use Helix\Asana\Project\Section;
use Helix\Asana\Tag;
use Helix\Asana\Task;
use Helix\Asana\Task\Attachment;
use Helix\Asana\Task\Story;
use Helix\Asana\Team;
use Helix\Asana\User;
use Helix\Asana\User\TaskList;
use Helix\Asana\Webhook\ProjectWebhook;
use Helix\Asana\Webhook\TaskWebhook;
use Helix\Asana\Workspace;
use Illuminate\Support\Facades\Facade;

/**
 * @see Api
 * @see AsanaServiceProvider
 *
 * @method static null|Attachment           getAttachment           (string $gid)
 * @method static null|CustomField          getCustomField          (string $gid)
 * @method static Workspace                 getDefaultWorkspace     ()
 * @method static null|Job                  getJob                  (string $gid)
 * @method static User                      getMe                   ()
 * @method static null|OrganizationExport   getOrganizationExport   (string $gid)
 * @method static null|Portfolio            getPortfolio            (string $gid)
 * @method static null|Project              getProject              (string $gid)
 * @method static null|ProjectWebhook       getProjectWebhook       (string $gid)
 * @method static null|Section              getSection              (string $gid)
 * @method static null|Story                getStory                (string $gid)
 * @method static null|Tag                  getTag                  (string $gid)
 * @method static null|Task                 getTask                 (string $gid)
 * @method static null|TaskList             getTaskList             (string $gid)
 * @method static null|TaskWebhook          getTaskWebhook          (string $gid)
 * @method static null|Team                 getTeam                 (string $gid)
 * @method static null|User                 getUser                 (string $gid)
 * @method static null|User                 getUserByEmail          (string $email)
 * @method static Event                     getWebhookEvent         (array $data)
 * @method static null|Workspace            getWorkspace            (string $gid)
 */
class Asana extends Facade
{

    /**
     * @return Api
     */
    public static function getApi()
    {
        return static::getFacadeRoot();
    }

    /**
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return AsanaServiceProvider::NAME;
    }

}