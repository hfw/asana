<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\ImmutableInterface;

/**
 * An asynchronous job.
 *
 * @immutable Jobs can only be polled.
 *
 * @see https://developers.asana.com/docs/asana-jobs
 * @see https://developers.asana.com/docs/job
 *
 * @method null|Project getNewProject       ()
 * @method null|Task    getNewTask          ()
 * @method string       getResourceSubtype  ()
 * @method string       getStatus           ()
 */
class Job extends AbstractEntity implements ImmutableInterface
{

    const DIR = 'jobs';
    const TYPE = 'job';
    const TYPE_DUPLICATE_PROJECT = 'duplicate_project';
    const TYPE_DUPLICATE_TASK = 'duplicate_task';

    const STATUS_QUEUED = 'not_started';
    const STATUS_ACTIVE = 'in_progress';
    const STATUS_SUCCESS = 'succeeded'; // api docs say "completed" but that's wrong.
    const STATUS_FAIL = 'failed';

    protected const MAP = [
        'new_project' => Project::class,
        'new_task' => Task::class
    ];

    /**
     * Whether the job is in progress.
     *
     * @return bool
     */
    final public function isActive(): bool
    {
        return $this->getStatus() === self::STATUS_ACTIVE;
    }

    /**
     * Whether the job has completed successfully or failed.
     *
     * @return bool
     */
    final public function isDone(): bool
    {
        return $this->isSuccessful() or $this->isFailed();
    }

    /**
     * @return bool
     */
    final public function isDuplicatingProject(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_DUPLICATE_PROJECT;
    }

    /**
     * @return bool
     */
    final public function isDuplicatingTask(): bool
    {
        return $this->getResourceSubtype() === self::TYPE_DUPLICATE_TASK;
    }

    /**
     * Whether the job failed.
     *
     * @return bool
     */
    final public function isFailed(): bool
    {
        return $this->getStatus() === self::STATUS_FAIL;
    }

    /**
     * Whether the job has yet to be started.
     *
     * @return bool
     */
    final public function isQueued(): bool
    {
        return $this->getStatus() === self::STATUS_QUEUED;
    }

    /**
     * Whether the job completed successfully.
     *
     * @return bool
     */
    final public function isSuccessful(): bool
    {
        return $this->getStatus() === self::STATUS_SUCCESS;
    }

    /**
     * Sleeps a few seconds between reloads until the job completes successfully or fails.
     *
     * A spinner can be called every sleep cycle to indicate progress.
     *
     * @param null|callable $spinner `fn( Job $this ): void`
     * @return $this
     */
    public function wait(callable $spinner = null)
    {
        while (!$this->isDone()) {
            if ($spinner) {
                call_user_func($spinner, $this);
            }
            sleep(3);
            $this->reload();
        }
        return $this;
    }
}