<?php

namespace Helix\Asana\Api;

use Closure;
use Helix\Asana\Api;
use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\Data;

/**
 * Pools entities in runtime memory.
 */
class Pool
{

    /**
     * `[ gid => entity ]`
     *
     * @var AbstractEntity[]
     */
    private array $entities = [];

    /**
     * `[ key => gid ]`
     *
     * @var string[];
     */
    private array $gids = [];

    /**
     * @param AbstractEntity $entity
     * @return void
     */
    protected function _add(AbstractEntity $entity): void
    {
        assert($entity->hasGid());
        $gid = $entity->getGid();
        $this->entities[$gid] = $entity;
        $this->gids[$gid] = $gid;
    }

    /**
     * @param AbstractEntity $entity
     * @param string[] $keys
     * @return void
     */
    protected function _addKeys(AbstractEntity $entity, string ...$keys): void
    {
        assert($entity->hasGid());
        $this->gids += array_fill_keys($keys, $entity->getGid());
    }

    /**
     * @param string $key
     * @param Api|Data $caller For hydration if needed.
     * @return null|AbstractEntity
     */
    protected function _get(string $key, Api|Data $caller): ?AbstractEntity
    {
        if (isset($this->gids[$key])) {
            return $this->entities[$this->gids[$key]];
        }
        return null;
    }

    /**
     * Polls. Does not guarantee a subsequent hit.
     *
     * @param string $key
     * @return bool
     */
    protected function _has(string $key): bool
    {
        return isset($this->gids[$key]);
    }

    /**
     * This is final to ensure pH balance.
     *
     * Subclasses must override the internal methods instead of this.
     *
     * @param AbstractEntity $entity
     * @return void
     */
    final public function add(AbstractEntity $entity): void
    {
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
     * @return null|AbstractEntity
     */
    final public function get(string $key, Api|Data $caller, Closure $factory): ?AbstractEntity
    {
        if ($entity = $this->_get($key, $caller)) {
            return $entity;
        }
        /** @var null|AbstractEntity $entity */
        if ($entity = $factory($caller)) {
            $gid = $entity->getGid();
            // was this a previously unknown dynamic key (i.e. "/users/me") that resulted in a duplicate?
            if ($this->_has($gid) and $pooled = $this->_get($gid, $caller)) { // poll & fetch
                if ($pooled->__merge($entity)) { // did the factory call result in new data?
                    $this->add($pooled); // renew underlying cache if present
                }
                $this->_addKeys($pooled, $key); // remember the dynamic key
                return $pooled;
            }
            $this->add($entity);
            $this->_addKeys($entity, $key);
        }
        return $entity;
    }

    /**
     * @param string[] $keys
     * @return void
     */
    public function remove(array $keys): void
    {
        foreach ($keys as $key) {
            unset($this->entities[$key]);
            unset($this->gids[$key]);
        }
    }

}