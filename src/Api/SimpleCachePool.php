<?php

namespace Helix\Asana\Api;

use Helix\Asana\Api;
use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\Data;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;

/**
 * Adapts a `PSR-16 SimpleCache` instance to the runtime entity pool.
 *
 * > :info:
 * > Requires `psr/simple-cache` (any version).
 *
 * Concurrency locks can be implemented by extending this class.
 *
 * @see https://www.php-fig.org/psr/psr-16/
 *
 * @see FileCache
 */
class SimpleCachePool extends Pool
{

    /**
     * @var CacheInterface
     */
    protected readonly CacheInterface $cache;

    /**
     * @var int
     */
    protected int $ttl = 3600;

    /**
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param AbstractEntity $entity
     * @return void
     */
    protected function _add(AbstractEntity $entity): void
    {
        assert($entity->hasGid());
        $this->cache->set("asana/{$entity->getGid()}", $entity, $this->ttl);
        parent::_add($entity);
    }

    /**
     * @param AbstractEntity $entity
     * @param string[] $keys
     * @return void
     */
    protected function _addKeys(AbstractEntity $entity, string ...$keys): void
    {
        assert($entity->hasGid());
        $gid = $entity->getGid();
        foreach ($keys as $key) {
            if ($key !== $gid) {
                $this->cache->set("asana/{$key}", $gid, $this->ttl);
            }
        }
        parent::_addKeys($entity, ...$keys);
    }

    /**
     * @param string $key
     * @param Api|Data $caller
     * @return null|AbstractEntity
     */
    protected function _get(string $key, Api|Data $caller): ?AbstractEntity
    {
        if ($entity = parent::_get($key, $caller)) {
            return $entity;
        }
        if ($entity = $this->cache->get("asana/{$key}")) {
            if (is_string($entity)) { // gid ref
                $this->log?->debug("CACHE-POOL BOUNCE asana/{$key} => {$entity}");
                if (!$entity = $this->_get($entity, $caller)) {
                    $this->log?->error("CACHE-POOL BAD-REF asana/{$key}");
                    $this->cache->delete("asana/{$key}"); // bad ref
                }
                return $entity;
            }
            $this->log?->debug("CACHE-POOL HIT asana/{$key}");
            /** @var AbstractEntity $entity unserialized */
            parent::_add($entity); // pool before hydration to make circular references safe.
            $data = (new ReflectionClass($entity))->getProperty('data')->getValue($entity);
            $entity->__construct($caller, $data); // hydrate via reconstruction
            parent::_addKeys($entity, $key, ...$entity->_getPoolKeys());
        }
        return $entity;
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function _has(string $key): bool
    {
        return parent::_has($key) or $this->cache->has("asana/{$key}");
    }

    /**
     * @return int
     */
    final public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * @param string[] $keys
     * @return void
     */
    public function remove(array $keys): void
    {
        parent::remove($keys);
        foreach ($keys as $key) {
            $this->log?->debug("CACHE-POOL REMOVE asana/{$key}");
            $this->cache->delete("asana/{$key}");
        }
    }

    /**
     * @param int $ttl
     * @return $this
     */
    final public function setTtl(int $ttl): static
    {
        $this->ttl = $ttl;
        return $this;
    }
}
