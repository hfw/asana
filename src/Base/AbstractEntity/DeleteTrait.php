<?php

namespace Helix\Asana\Base\AbstractEntity;

/**
 * Adds `delete()` to entities.
 */
trait DeleteTrait
{

    /**
     * Deletes the entity from Asana.
     *
     * @return void
     */
    public function delete(): void
    {
        $this->api->delete($this);
        $this->api->getPool()->remove($this->getPoolKeys());
    }
}