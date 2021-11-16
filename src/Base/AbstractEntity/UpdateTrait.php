<?php

namespace Helix\Asana\Base\AbstractEntity;

use Helix\Asana\Base\AbstractEntity;

/**
 * Adds `update()` to entities.
 *
 * @mixin AbstractEntity
 */
trait UpdateTrait
{

    /**
     * `PUT` the data diff to Asana, if there is one.
     *
     * @return $this
     */
    public function update()
    {
        if ($this->isDiff()) {
            /** @var array $remote */
            $remote = $this->api->put($this, $this->toArray(true), ['expand' => 'this']);
            $this->_setData($remote);
            /** @var AbstractEntity $that */
            $that = $this;
            $this->api->getPool()->add($that);
        }
        return $this;
    }
}