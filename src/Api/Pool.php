<?php

namespace Helix\Asana\Api;

use Closure;
use Helix\Asana\Api;
use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\Data;

/**
 * Pools entities in runtime memory.
 */
class Pool {

    /**
     * `[ gid => entity ]`
     *
     * @var AbstractEntity[]
     */
    private $entities = [];

    /**
     * `[ key => gid ]`
     *
     * @var string[];
     */
    private $gids = [];

    /**
     * @param AbstractEntity $entity
     */
    protected function _add (AbstractEntity $entity): void {
        assert($entity->hasGid());
        $gid = $entity->getGid();
        $this->entities[$gid] = $entity;
        $this->gids[$gid] = $gid;
    }

    /**
     * @param AbstractEntity $entity
     * @param string[] $keys
     */
    protected function _addKeys (AbstractEntity $entity, ...$keys): void {
        assert($entity->hasGid());
        $this->gids += array_fill_keys($keys, $entity->getGid());
    }

    /**
     * @param string $key
     * @param Api|Data $caller For hydration if needed.
     * @return null|AbstractEntity
     */
    protected function _get (string $key, $caller) {
        if (isset($this->gids[$key])) {
            return $this->entities[$this->gids[$key]];
        }
        unset($caller);
        return null;
    }

    /**
     * Polls. The entity may be gone by the time this returns (cache race).
     *
     * @param string $key
     * @return bool
     */
    protected function _has (string $key): bool {
        return isset($this->gids[$key]);
    }

    /**
     * This is final to ensure pH balance.
     *
     * Subclasses must override the internal methods instead of this.
     *
     * @param AbstractEntity $entity
     */
    final public function add (AbstractEntity $entity): void {
        assert($entity->hasGid());
        if (!$entity->isDiff()) {
            $this->_add($entity);
            $this->_addKeys($entity, ...$entity->getPoolKeys());
        }
    }

    /**
     * This is final to ensure pH balance.
     *
     * Subclasses must override the internal methods instead of this.
     *
     * @param string $key
     * @param Api|Data $caller
     * @param Closure $factory `fn( Api|Data $caller ): null|AbstractEntity`
     * @return null|mixed|AbstractEntity
     */
    final public function get (string $key, $caller, Closure $factory) {
        /** @var AbstractEntity $entity */
        if (!$entity = $this->_get($key, $caller) and $entity = $factory($caller)) {
            $gid = $entity->getGid();
            // duplicate with dynamic key? (e.g. "/users/me")
            if ($this->_has($gid) and $pooled = $this->_get($gid, $caller)) { // poll & fetch
                if ($pooled->__merge($entity)) { // new data?
                    $this->add($pooled); // renew everything
                }
                $this->_addKeys($pooled, $key);
                return $pooled;
            }
            $this->add($entity);
            $this->_addKeys($entity, $key);
        }
        return $entity;
    }

    /**
     * @param string[] $keys
     */
    public function remove (array $keys): void {
        foreach ($keys as $key) {
            unset($this->entities[$key]);
            unset($this->gids[$key]);
        }
    }

}