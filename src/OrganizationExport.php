<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CreateTrait;

/**
 * An organization export.
 *
 * @see https://developers.asana.com/docs/asana-organization-exports
 *
 * @method string       getCreatedAt    () RFC3339x
 * @method null|string  getDownloadUrl  ()
 * @method string       getState        ()
 * @method Workspace    getOrganization ()
 */
class OrganizationExport extends AbstractEntity {

    use CreateTrait {
        create as private _create;
    }

    const DIR = 'organization_exports';
    const TYPE = 'organization_export';
    const STATE_QUEUED = 'pending';
    const STATE_ACTIVE = 'started';
    const STATE_SUCCESS = 'finished';
    const STATE_FAIL = 'error';

    protected const MAP = [
        'organization' => Workspace::class
    ];

    /**
     * @param Workspace $organization
     * @return $this
     */
    public function create (Workspace $organization) {
        $this->_set('organization', $organization);
        return $this->_create();
    }

    /**
     * @return null
     */
    protected function getParentNode () {
        return null;
    }

    /**
     * Whether the export is in progress.
     *
     * @return bool
     */
    final public function isActive (): bool {
        return $this->getState() === self::STATE_ACTIVE;
    }

    /**
     * Whether the export has completed successfully or failed.
     *
     * @return bool
     */
    final public function isDone (): bool {
        return $this->isSuccessful() or $this->isFailed();
    }

    /**
     * Whether the export failed.
     *
     * @return bool
     */
    final public function isFailed (): bool {
        return $this->getState() === self::STATE_FAIL;
    }

    /**
     * Whether the export has yet to be started.
     *
     * @return bool
     */
    final public function isQueued (): bool {
        return $this->getState() === self::STATE_QUEUED;
    }

    /**
     * Whether the export completed successfully.
     *
     * @return bool
     */
    final public function isSuccessful (): bool {
        return $this->getState() === self::STATE_SUCCESS;
    }

    /**
     * Sleeps a minute between reloads until the export completes successfully or fails.
     *
     * A spinner can be called every sleep cycle to indicate progress.
     *
     * @param null|callable $spinner `fn( OrganizationExport $this ): void`
     * @return $this
     */
    public function wait (callable $spinner = null) {
        while (!$this->isDone()) {
            if ($spinner) {
                call_user_func($spinner, $this);
            }
            sleep(60);
            $this->reload();
        }
        return $this;
    }
}