<?php

namespace Helix\Asana\Base\AbstractEntity;

/**
 * Adds `update()` to entities.
 */
trait UpdateTrait
{

    /**
     * `PUT` the data diff to Asana, if there is one.
     *
     * @return $this
     */
    public function update(): static
    {
        if ($this->isDiff()) {
            $remote = $this->api->put($this, $this->toArray(true), ['expand' => 'this']);
            $this->_setData($remote);
            $this->api->getPool()->add($this);
        }
        return $this;
    }
}