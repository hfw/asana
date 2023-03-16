<?php

namespace Helix\Asana\Api;

use DateInterval;
use DateTime;
use Helix\Asana\Base\LogTrait;
use Psr\SimpleCache\CacheInterface;

/**
 * An optional bare bones PSR-16 "compliant" file-based cache for {@link SimpleCachePool}.
 *
 * > :info:
 * > Requires `psr/simple-cache` (any version).
 *
 * > :warning:
 * > This is not safe for concurrency.
 *
 * Don't use this if you have a better cache.
 */
final class FileCache implements CacheInterface
{

    use LogTrait;

    /**
     * @var string
     */
    private readonly string $dir;

    /**
     * @param string $dir
     */
    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    /**
     * @param string $key
     * @return string
     */
    private function _path(string $key): string
    {
        $path = "{$this->dir}/{$key}~";
        clearstatcache(true, $path);
        return $path;
    }

    /**
     * @param string $key
     * @return string
     */
    private function _ref(string $key): string
    {
        return "{$this->dir}/{$key}.ref";
    }

    /**
     * Does nothing.
     *
     * @return bool `false`
     */
    public function clear(): bool
    {
        return false;
    }

    /**
     * `CacheInterface:1|2` compatible params.
     *
     * @param string $key
     * @return bool
     */
    public function delete($key): bool
    {
        $path = $this->_path($key);
        if (is_link($ref = $this->_ref($key))) {
            $this->log?->debug("CACHE DELINK {$key}");
            unlink($ref);
            unlink($path);
        } elseif (is_file($path)) {
            $this->log?->debug("CACHE DELETE {$key}");
            unlink($path);
        }
        return true;
    }

    /**
     * `CacheInterface:1|2` compatible params.
     *
     * @param iterable $keys
     * @return bool
     */
    public function deleteMultiple($keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * `CacheInterface:1|2` compatible params.
     *
     * @param string $key
     * @param mixed $default
     * @return string|object|mixed `$default` is the only mixed type returned
     */
    public function get($key, $default = null): mixed
    {
        $path = $this->_path($key);
        if (!is_file($path)) {
            $this->log?->debug("CACHE MISS {$key}");
            return $default;
        }
        if (filemtime($path) <= time()) {
            $this->log?->debug("CACHE EXPIRE {$key}");
            unlink($path);
            return $default;
        }
        $data = unserialize(file_get_contents($path));
        $this->log?->debug(is_object($data)
            ? "CACHE HIT {$key} => " . get_class($data)
            : "CACHE BOUNCE {$key} => {$data}"
        );
        return $data;
    }

    /**
     * `CacheInterface:1|2` compatible params.
     *
     * @param iterable $keys
     * @param mixed $default
     * @return array
     */
    public function getMultiple($keys, $default = null): array
    {
        $return = [];
        foreach ($keys as $key) {
            $return[$key] = $this->get($key, $default);
        }
        return $return;
    }

    /**
     * `CacheInterface:1|2` compatible params.
     *
     * This only checks for file existence, regardless of expiration.
     * This is fine, since by principle, cache polling does not guarantee a subsequent hit.
     *
     * @param string $key
     * @return bool
     */
    public function has($key): bool
    {
        return is_file($this->_path($key));
    }

    /**
     * `CacheInterface:1|2` compatible params.
     *
     * @param string $key
     * @param string|object $value
     * @param null|int|DateInterval $ttl `NULL` is the same as `0` seconds.
     * @return bool
     */
    public function set($key, $value, $ttl = null): bool
    {
        $path = $this->_path($key);
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        if (is_object($value)) {
            $this->log?->debug(is_file($path) ? "CACHE UPDATE {$key}" : "CACHE SET {$key}");
        } else {
            $this->log?->debug(is_file($path)
                ? "CACHE RENEW LINK {$key} => {$value}"
                : "CACHE LINK {$key} => {$value}"
            );
            if (!is_link($ref = $this->_ref($key))) {
                symlink($this->_path("asana/{$value}"), $ref);
            }
        }
        file_put_contents($path, serialize($value));
        chmod($path, 0660);
        touch($path, $ttl instanceof DateInterval
            ? (new DateTime())->add($ttl)->getTimestamp()
            : time() + $ttl
        );
        return true;
    }

    /**
     * `CacheInterface:1|2` compatible params.
     *
     * @param iterable $values
     * @param int|DateInterval $ttl `NULL` is the same as `0` seconds.
     * @return bool
     */
    public function setMultiple($values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }
}
