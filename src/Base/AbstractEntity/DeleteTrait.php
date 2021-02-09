<?php

namespace Helix\Asana\Base\AbstractEntity;

use Helix\Asana\Base\AbstractEntity;

/**
 * Adds `delete()` to entities.
 *
 * @mixin AbstractEntity
 */
trait DeleteTrait {

    /**
     * `DELETE`
     */
    public function delete (): void {
        $this->api->delete($this);
        $this->api->getPool()->remove($this->getPoolKeys());
    }
}