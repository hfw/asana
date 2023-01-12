<?php

namespace Helix\Asana;

use Generator;
use Helix\Asana\Api\AsanaError;
use Helix\Asana\Api\Pool;
use Helix\Asana\Base\Data;
use Helix\Asana\Project\Section;
use Helix\Asana\Task\Attachment;
use Helix\Asana\Task\Story;
use Helix\Asana\User\TaskList;
use Helix\Asana\Webhook\ProjectWebhook;
use Helix\Asana\Webhook\TaskWebhook;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * API access.
 *
 * @see https://app.asana.com/-/developer_console
 */
class Api
{

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $log;

    /**
     * @var Pool
     */
    private readonly Pool $pool;

    /**
     * @var string
     */
    protected string $token;

    /**
     * Default workspace GID.
     *
     * @var null|string
     */
    protected ?string $workspace = null;

    /**
     * @param string $token
     * @param null|Pool $pool
     */
    public function __construct(string $token, Pool $pool = null)
    {
        $this->token = $token;
        $this->pool = $pool ?? new Pool();
    }

    /**
     * cURL transport.
     *
     * @param string $method
     * @param string $path
     * @param array $curlOpts
     * @return null|array
     * @throws AsanaError
     */
    public function call(string $method, string $path, array $curlOpts = []): ?array
    {
        $this->getLog()->debug("Asana {$method} {$path}", $curlOpts);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_URL => "https://app.asana.com/api/1.0/{$path}",
            CURLOPT_USERAGENT => 'hfw/asana',
            CURLOPT_FOLLOWLOCATION => false, // HTTP 201 includes Location
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true
        ]);
        $curlOpts[CURLOPT_HTTPHEADER][] = "Authorization: Bearer {$this->token}";
        $curlOpts[CURLOPT_HTTPHEADER][] = 'Accept: application/json';
        $curlOpts[CURLOPT_HTTPHEADER][] = 'Expect:'; // prevent http 100
        curl_setopt_array($ch, $curlOpts);
        RETRY:
        $res = explode("\r\n\r\n", curl_exec($ch), 2);
        $info = curl_getinfo($ch);
        switch ($info['http_code']) {
            case 0:
                throw new AsanaError(curl_errno($ch), curl_error($ch), $info);
            case 200:
            case 201:
                return json_decode($res[1], true, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
            case 404:
                return null;
            case 429:
                preg_match('/^Retry-After:\h*(\d+)/im', $res[0], $retry);
                $this->getLog()->debug("Asana {$retry[0]}");
                sleep($retry[1]);
                goto RETRY;
            default:
                $this->getLog()->error("Asana {$info['http_code']}: {$res[1]}");
            case 412: // normal sync error. skip log.
                throw new AsanaError($info['http_code'], $res[1], $info);
        }
    }

    /**
     * `HTTP DELETE`
     *
     * @param string $path
     * @return void
     */
    public function delete(string $path): void
    {
        $this->call('DELETE', $path);
    }

    /**
     * The central point of object creation.
     *
     * This can be overridden to return custom extensions.
     *
     * @template T of Data
     * @param Api|Data $caller
     * @param class-string<T> $class
     * @param array $data
     * @return T
     */
    public function factory($caller, string $class, array $data = [])
    {
        return new $class($caller, $data);
    }

    /**
     * `HTTP GET` for `data` within a single result.
     *
     * @param string $path
     * @param array $query
     * @return null|array
     */
    public function get(string $path, array $query = []): ?array
    {
        return $this->call('GET', $path . '?' . http_build_query($query))['data'] ?? null;
    }

    /**
     * Loads an {@link Attachment}.
     *
     * @param string $gid
     * @return null|Attachment
     */
    public function getAttachment(string $gid): ?Attachment
    {
        return $this->load($this, Attachment::class, "attachments/{$gid}");
    }

    /**
     * Loads a {@link CustomField}.
     *
     * @param string $gid
     * @return null|CustomField
     */
    public function getCustomField(string $gid): ?CustomField
    {
        return $this->load($this, CustomField::class, "custom_fields/{$gid}");
    }

    /**
     * `HTTP GET` for multiple results, with auto-pagination.
     *
     * @param string $path
     * @param array $query
     * @return Generator<array>
     */
    public function getEach(string $path, array $query = []): Generator
    {
        $query['opt_expand'] = 'this';
        $remain = $query['limit'] ?? PHP_INT_MAX;
        do {
            $query['limit'] = min($remain, 100);
            $page = $this->call('GET', $path . '?' . http_build_query($query));
            foreach ($page['data'] ?? [] as $data) {
                yield $data;
                $remain--;
            }
            $query['offset'] = $page['next_page']['offset'] ?? null;
        } while ($remain and $query['offset']);
    }

    /**
     * @param string $gid
     * @return null|Job
     */
    public function getJob(string $gid): ?Job
    {
        return $this->load($this, Job::class, "jobs/{$gid}");
    }

    /**
     * @return LoggerInterface
     */
    public function getLog(): LoggerInterface
    {
        return $this->log ??= new NullLogger();
    }

    /**
     * @return User
     */
    public function getMe(): User
    {
        return $this->getUser('me');
    }

    /**
     * @param string $gid
     * @return null|OrganizationExport
     */
    public function getOrganizationExport(string $gid): ?OrganizationExport
    {
        return $this->load($this, OrganizationExport::class, "organization_exports/{$gid}");
    }

    /**
     * @return Pool
     */
    public function getPool(): Pool
    {
        return $this->pool;
    }

    /**
     * Loads a {@link Portfolio}.
     *
     * @param string $gid
     * @return null|Portfolio
     */
    public function getPortfolio(string $gid): ?Portfolio
    {
        return $this->load($this, Portfolio::class, "portfolios/{$gid}");
    }

    /**
     * Loads a {@link Project}.
     *
     * @param string $gid
     * @return null|Project
     */
    public function getProject(string $gid): ?Project
    {
        return $this->load($this, Project::class, "projects/{$gid}");
    }

    /**
     * @param string $gid
     * @return null|ProjectWebhook
     */
    public function getProjectWebhook(string $gid): ?ProjectWebhook
    {
        return $this->load($this, ProjectWebhook::class, "webhooks/{$gid}");
    }

    /**
     * Loads a {@link Section}.
     *
     * @param string $gid
     * @return null|Section
     */
    public function getSection(string $gid): ?Section
    {
        return $this->load($this, Section::class, "sections/{$gid}");
    }

    /**
     * Loads a {@link Story}.
     *
     * @param string $gid
     * @return null|Story
     */
    public function getStory(string $gid): ?Story
    {
        return $this->load($this, Story::class, "stories/{$gid}");
    }

    /**
     * Loads a {@link Tag}.
     *
     * @param string $gid
     * @return null|Tag
     */
    public function getTag(string $gid): ?Tag
    {
        return $this->load($this, Tag::class, "tags/{$gid}");
    }

    /**
     * Loads a {@link Task}.
     *
     * @param string $gid
     * @return null|Task
     */
    public function getTask(string $gid): ?Task
    {
        return $this->load($this, Task::class, "tasks/{$gid}");
    }

    /**
     * Loads a {@link TaskList}.
     *
     * @param string $gid
     * @return null|TaskList
     */
    public function getTaskList(string $gid): ?TaskList
    {
        return $this->load($this, TaskList::class, "user_task_lists/{$gid}");
    }

    /**
     * @param string $gid
     * @return null|TaskWebhook
     */
    public function getTaskWebhook(string $gid): ?TaskWebhook
    {
        return $this->load($this, TaskWebhook::class, "webhooks/{$gid}");
    }

    /**
     * Loads a {@link Team}.
     *
     * @param string $gid
     * @return null|Team
     */
    public function getTeam(string $gid): ?Team
    {
        return $this->load($this, Team::class, "teams/{$gid}");
    }

    /**
     * Loads a {@link User}.
     *
     * @param string $gid
     * @return null|User
     */
    public function getUser(string $gid): ?User
    {
        return $this->load($this, User::class, "users/{$gid}");
    }

    /**
     * Gets a user in the default workspace by email.
     *
     * @see Workspace::getUserByEmail()
     * @param string $email
     * @return null|User
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->getWorkspace()->getUserByEmail($email);
    }

    /**
     * Expands received webhook data as a full event object.
     *
     * @see https://developers.asana.com/docs/event
     *
     * @param array $data
     * @return Event
     */
    public function getWebhookEvent(array $data): Event
    {
        return $this->factory($this, Event::class, $data);
    }

    /**
     * Loads a {@link Workspace}.
     *
     * @param null|string $gid Defaults to {@link $workspace} or the API user's first-known workspace.
     * @return null|Workspace
     */
    public function getWorkspace(string $gid = null): ?Workspace
    {
        $gid ??= $this->workspace;
        return isset($gid)
            ? $this->load($this, Workspace::class, "workspaces/{$gid}")
            : $this->getMe()->getWorkspaces()[0];
    }

    /**
     * Loads the entity found at the given path + query.
     *
     * @template T
     * @param Api|Data $caller
     * @param class-string<T> $class
     * @param string $path
     * @param array $query
     * @return null|T
     */
    public function load($caller, string $class, string $path, array $query = [])
    {
        $key = rtrim($path . '?' . http_build_query($query), '?');
        $query['opt_expand'] = 'this';
        return $this->pool->get($key, $caller, function ($caller) use ($class, $path, $query) {
            $data = $this->get($path, $query);
            return $data ? $this->factory($caller, $class, $data) : null;
        });
    }

    /**
     * All results from {@link loadEach()}
     *
     * @template T
     * @param Api|Data $caller
     * @param class-string<T> $class
     * @param string $path
     * @param array $query
     * @return T[]
     */
    public function loadAll($caller, string $class, string $path, array $query = [])
    {
        return iterator_to_array($this->loadEach(...func_get_args()));
    }

    /**
     * Loads and yields each entity found at the given path + query.
     *
     * The result-set is not pooled, but individual entities are.
     *
     * @template T
     * @param Api|Data $caller
     * @param class-string<T> $class
     * @param string $path
     * @param array $query `limit` can exceed `100` here.
     * @return Generator<T>
     */
    public function loadEach($caller, string $class, string $path, array $query = []): Generator
    {
        foreach ($this->getEach($path, $query) as $data) {
            yield $this->pool->get($data['gid'], $caller, fn($caller) => $this->factory($caller, $class, $data));
        }
    }

    /**
     * `HTTP POST`
     *
     * @param string $path
     * @param array $data
     * @param array $options
     * @return null|array
     */
    public function post(string $path, array $data = [], array $options = []): ?array
    {
        return $this->call('POST', $path, [
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'options' => $options,
                'data' => $data
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        ])['data'] ?? null;
    }

    /**
     * `HTTP PUT`
     *
     * @param string $path
     * @param array $data
     * @param array $options
     * @return null|array
     */
    public function put(string $path, array $data = [], array $options = []): ?array
    {
        return $this->call('PUT', $path, [
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'options' => $options,
                'data' => $data
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        ])['data'] ?? null;
    }

    /**
     * @param LoggerInterface $log
     * @return $this
     */
    final public function setLog(LoggerInterface $log): static
    {
        $this->log = $log;
        return $this;
    }

    /**
     * @param null|string $gid
     * @return $this
     */
    final public function setWorkspace(?string $gid): static
    {
        $this->workspace = $gid;
        return $this;
    }
}