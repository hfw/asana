<?php

namespace Helix\Asana\Base\AbstractEntity;

/**
 * Adds `delete()` to entities.
 */
trait DeleteTrait
{

    /**
     * `DELETE`
     */
    public function delete(): void
    {
        $this->api->delete($this);
        $this->api->getPool()->remove($this->getPoolKeys());
    }
}