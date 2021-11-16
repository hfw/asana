<?php

namespace Helix\Asana\Base\AbstractEntity;

use Helix\Asana\Base\AbstractEntity;

/**
 * Adds `create()` to entities.
 *
 * @mixin AbstractEntity
 */
trait CreateTrait
{

    /**
     * The parent entity, if any, needed for creation.
     *
     * @return null|AbstractEntity|mixed
     */
    abstract protected function getParentNode();

    /**
     * Creates the new entity in Asana.
     *
     * @return $this
     */
    public function create()
    {
        assert(!$this->hasGid());
        $path = static::DIR;
        if ($parent = $this->getParentNode()) {
            assert($parent->hasGid());
            $path = "{$parent}/{$path}";
        }
        $remote = $this->api->post($path, $this->toArray(true), ['expand' => 'this']);
        $this->_setData($remote);
        /** @var AbstractEntity $that */
        $that = $this;
        $this->api->getPool()->add($that);
        return $this;
    }
}