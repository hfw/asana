<?php

namespace Helix\Asana\Api;

use Helix\Asana\Api;
use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\ImmutableInterface;
use Helix\Asana\Base\Data;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface as PSR16;

/**
 * Adapts a `PSR-16 SimpleCache` instance to the runtime entity pool.
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
     * @var PSR16
     */
    protected $psr;

    /**
     * @var int
     */
    protected $ttl = 3600;

    /**
     * @param PSR16 $psr
     */
    public function __construct(PSR16 $psr)
    {
        $this->psr = $psr;
    }

    /**
     * @param AbstractEntity $entity
     * @throws CacheException
     */
    protected function _add(AbstractEntity $entity): void
    {
        assert($entity->hasGid());
        $this->psr->set("asana/{$entity->getGid()}", $entity, $this->_getTtl($entity));
        parent::_add($entity);
    }

    /**
     * @param AbstractEntity $entity
     * @param string[] $keys
     * @throws CacheException
     */
    protected function _addKeys(AbstractEntity $entity, ...$keys): void
    {
        assert($entity->hasGid());
        $gid = $entity->getGid();
        $ttl = $this->_getTtl($entity);
        foreach ($keys as $key) {
            if ($key !== $gid) {
                $this->psr->set("asana/{$key}", $gid, $ttl);
            }
        }
        parent::_addKeys($entity, ...$keys);
    }

    /**
     * @param string $key
     * @param Api|Data $caller
     * @return null|AbstractEntity
     * @throws CacheException
     */
    protected function _get(string $key, $caller)
    {
        if (!$entity = parent::_get($key, $caller) and $entity = $this->psr->get("asana/{$key}")) {
            if (is_string($entity)) { // gid ref
                if (!$entity = $this->_get($entity, $caller)) {
                    $this->psr->delete("asana/{$key}"); // bad ref
                }
                return $entity;
            }
            /** @var AbstractEntity $entity */
            parent::_add($entity); // pool before hydration to make circular references safe.
            $entity->__construct($caller, $entity->__debugInfo()); // hydrate
            parent::_addKeys($entity, $key, ...$entity->getPoolKeys());
        }
        return $entity;
    }

    /**
     * @param AbstractEntity $entity
     * @return int
     */
    protected function _getTtl(AbstractEntity $entity): int
    {
        if ($entity instanceof ImmutableInterface) {
            return strtotime('tomorrow') - time();
        }
        return $this->ttl;
    }

    /**
     * @param string $key
     * @return bool
     * @throws CacheException
     */
    protected function _has(string $key): bool
    {
        return parent::_has($key) or $this->psr->has("asana/{$key}");
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
     * @throws CacheException
     */
    public function remove(array $keys): void
    {
        parent::remove($keys);
        foreach ($keys as $key) {
            $this->psr->delete("asana/{$key}");
        }
    }

    /**
     * @param int $ttl
     * @return $this
     */
    final public function setTtl(int $ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }
}