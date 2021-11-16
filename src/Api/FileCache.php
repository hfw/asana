<?php

namespace Helix\Asana\Api;

use Helix\Asana\Base\AbstractEntity\ImmutableInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

/**
 * An optional bare bones PSR-16 "compliant" file-based cache for {@link SimpleCachePool}.
 *
 * Use this if you don't have a better cache.
 *
 * This is not safe for concurrency.
 */
final class FileCache implements CacheInterface
{

    /**
     * @var string
     */
    private $dir;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @param string $dir
     */
    public function __construct(string $dir)
    {
        $this->dir = $dir;
        $this->log = new NullLogger();
    }

    private function _log(string $msg): void
    {
        $this->log->debug($msg);
    }

    private function _path($key): string
    {
        $path = "{$this->dir}/{$key}~";
        clearstatcache(true, $path);
        return $path;
    }

    private function _ref($key): string
    {
        return "{$this->dir}/{$key}.ref";
    }

    public function clear()
    {
        // unused. just delete the dir.
    }

    public function delete($key)
    {
        $path = $this->_path($key);
        if (is_link($ref = $this->_ref($key))) {
            $this->_log("CACHE DELINK {$key}");
            unlink($ref);
            unlink($path);
        } elseif (is_file($path)) {
            $this->_log("CACHE DELETE {$key}");
            unlink($path);
        }
    }

    public function deleteMultiple($keys)
    {
        // unused
    }

    /**
     * @param string $key
     * @param mixed $default Unused.
     * @return null|string|object
     */
    public function get($key, $default = null)
    {
        $path = $this->_path($key);
        if (!is_file($path)) {
            $this->_log("CACHE MISS {$key}");
            return null;
        }
        if (filemtime($path) <= time()) {
            $this->_log("CACHE EXPIRE {$key}");
            unlink($path);
            return null;
        }
        $data = unserialize(file_get_contents($path));
        $this->_log(is_object($data)
            ? "CACHE HIT {$key} => " . get_class($data)
            : "CACHE BOUNCE {$key} => {$data}"
        );
        return $data;
    }

    public function getMultiple($keys, $default = null)
    {
        // unused
    }

    public function has($key): bool
    {
        return is_file($this->_path($key));
    }

    /**
     * @param string $key
     * @param string|object $value
     * @param int $ttl
     * @return void
     */
    public function set($key, $value, $ttl = null): void
    {
        $path = $this->_path($key);
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        if (is_object($value)) {
            $this->_log([
                ["CACHE SET {$key}", "CACHE BURN {$key}"][$value instanceof ImmutableInterface],
                "CACHE UPDATE {$key}"
            ][is_file($path)]);
        } else {
            $this->_log([
                "CACHE LINK {$key} => {$value}",
                "CACHE RENEW LINK {$key} => {$value}"
            ][is_file($path)]);
            if (!is_link($ref = $this->_ref($key))) {
                symlink($this->_path("asana/{$value}"), $ref);
            }
        }
        file_put_contents($path, serialize($value));
        chmod($path, 0660);
        touch($path, time() + $ttl);
    }

    /**
     * @param LoggerInterface $log
     * @return $this
     */
    public function setLog(LoggerInterface $log)
    {
        $this->log = $log;
        return $this;
    }

    public function setMultiple($values, $ttl = null)
    {
        // unused
    }
}