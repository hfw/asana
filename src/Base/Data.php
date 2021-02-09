<?php

namespace Helix\Asana\Base;

use Helix\Asana\Api;
use JsonSerializable;
use Serializable;

/**
 * A data object with support for annotated magic methods.
 */
class Data implements JsonSerializable, Serializable {

    /**
     * Sub-element hydration specs.
     *
     * - `field => class` for a nullable instance
     * - `field => [class]` for an array of instances
     *
     * The classes specified here should be the field's base class (identity) from this library.
     *
     * Do not use this to map to extensions. Extend and override {@link Api::factory()} to do that.
     *
     * @see _setField()
     *
     * @var array
     */
    protected const MAP = [];

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var array|Data[]|AbstractEntity[]
     */
    protected $data = [];

    /**
     * @var bool[]
     */
    protected $diff = [];

    /**
     * @param Api|Data $caller
     * @param array $data
     */
    public function __construct ($caller, array $data = []) {
        if ($caller instanceof self) {
            $this->api = $caller->api;
        }
        else {
            assert($caller instanceof Api);
            /** @var Api $caller */
            $this->api = $caller;
        }
        $this->_setData($data);
    }

    /**
     * Magic method handler.
     *
     * @see _get()
     * @see _has()
     * @see _is()
     * @see _select()
     * @see _set()
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call (string $method, array $args) {
        static $magic = [];
        if (!$call =& $magic[$method]) {
            preg_match('/^(get|has|is|select|set)(.+)$/', $method, $call);
            if ('_select' !== $call[1] = '_' . $call[1]) { // _select() calls getters
                $call[2] = preg_replace_callback('/[A-Z]/', function(array $match) {
                    return '_' . strtolower($match[0]);
                }, lcfirst($call[2]));
            }
        }
        return $this->{$call[1]}($call[2], ...$args);
    }

    /**
     * @return array
     * @internal pool, `var_export()`
     */
    final public function __debugInfo (): array {
        return $this->data;
    }

    /**
     * @param $field
     * @return null|Data|mixed
     * @internal for `array_column()`
     */
    final public function __get ($field) {
        return $this->_get($field);
    }

    /**
     * @param $field
     * @return bool
     * @internal for `array_column()`
     */
    final public function __isset ($field) {
        return true; // fields may be lazy-loaded or coalesced to null.
    }

    /**
     * Magic method: `getField()`
     *
     * @see __call()
     *
     * @param string $field
     * @return mixed
     */
    protected function _get (string $field) {
        return $this->data[$field] ?? null;
    }

    /**
     * Magic method: `hasField()`
     *
     * Whether a countable field has anything in it,
     * or casts a scalar field to boolean.
     *
     * @see __call()
     *
     * @param string $field
     * @return bool
     */
    protected function _has (string $field): bool {
        $value = $this->_get($field);
        if (isset($value)) {
            if (is_countable($value)) {
                return count($value) > 0;
            }
            return (bool)$value;
        }
        return false;
    }

    /**
     * A factory that also hydrates / caches entities.
     *
     * @param string $class
     * @param mixed $item
     * @return mixed
     */
    protected function _hydrate (string $class, $item) {
        if (!isset($item) or $item instanceof self) {
            return $item;
        }
        // hydrate entities
        if (is_subclass_of($class, AbstractEntity::class)) {
            if (is_string($item)) { // convert gids to lazy stubs
                $item = ['gid' => $item];
            }
            return $this->api->getPool()->get($item['gid'], $this, function() use ($class, $item) {
                return $this->api->factory($this, $class, $item);
            });
        }
        // hydrate simple
        return $this->api->factory($this, $class, $item);
    }

    /**
     * Magic method: `isField()`
     *
     * Boolean casts a scalar field.
     *
     * Do not use this for countable fields, use `hasField()` instead.
     *
     * @see __call()
     *
     * @param string $field
     * @return bool
     */
    protected function _is (string $field): bool {
        return (bool)$this->_get($field);
    }

    /**
     * Magic method: `selectField(callable $filter)`
     *
     * Where `Field` has an accessor at `getField()`, either real or magic.
     *
     * This can also be used to select from an arbitrary iterable.
     *
     * @see __call()
     *
     * @param string|iterable $subject
     * @param callable $filter `fn( Data $object ): bool`
     * @param array $args
     * @return array
     */
    protected function _select ($subject, callable $filter, ...$args) {
        if (is_string($subject)) {
            $subject = $this->{'get' . $subject}(...$args) ?? [];
        }
        $selected = [];
        foreach ($subject as $item) {
            if (call_user_func($filter, $item)) {
                $selected[] = $item;
            }
        }
        return $selected;
    }

    /**
     * Magic method: `setField(mixed $value)`
     *
     * @see __call()
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    protected function _set (string $field, $value) {
        $this->data[$field] = $value;
        $this->diff[$field] = true;
        return $this;
    }

    /**
     * Clears all diffs and sets all data, hydrating mapped fields.
     *
     * @param array $data
     */
    protected function _setData (array $data): void {
        $this->data = $this->diff = [];
        foreach ($data as $field => $value) {
            $this->_setField($field, $value);
        }
    }

    /**
     * Sets a value, hydrating if mapped, and clears the diff.
     *
     * @param string $field
     * @param mixed $value
     */
    protected function _setField (string $field, $value): void {
        if (isset(static::MAP[$field])) {
            $class = static::MAP[$field];
            if (is_array($class)) {
                $value = array_map(function($each) use ($class) {
                    return $this->_hydrate($class[0], $each);
                }, $value);
            }
            elseif (isset($value)) {
                $value = $this->_hydrate($class, $value);
            }
        }
        $this->data[$field] = $value;
        unset($this->diff[$field]);
    }

    /**
     * Whether the instance has changes.
     *
     * @return bool
     */
    final public function isDiff (): bool {
        return (bool)$this->diff;
    }

    /**
     * @return array
     */
    public function jsonSerialize (): array {
        $data = $this->toArray();
        ksort($data);
        return $data;
    }

    /**
     * Dehydrated JSON encode.
     *
     * @return string
     */
    public function serialize (): string {
        return json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Dehydrated data.
     *
     * @param bool $diff
     * @return array
     */
    public function toArray (bool $diff = false): array {
        $dehydrate = function($each) use (&$dehydrate, $diff) {
            // convert entities to gids
            if ($each instanceof AbstractEntity and $each->hasGid()) {
                return $each->getGid();
            }
            // convert other data to arrays.
            elseif ($each instanceof self) {
                return $each->toArray($diff);
            }
            // dehydrate normal arrays.
            elseif (is_array($each)) {
                return array_map($dehydrate, $each);
            }
            // return as-is
            return $each;
        };
        if ($diff) {
            return array_map($dehydrate, array_intersect_key($this->data, $this->diff));
        }
        return array_map($dehydrate, $this->data);
    }

    /**
     * @param $serialized
     */
    public function unserialize ($serialized): void {
        $this->data = json_decode($serialized, true);
    }
}