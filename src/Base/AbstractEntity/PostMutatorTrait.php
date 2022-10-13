<?php

namespace Helix\Asana\Base\AbstractEntity;

use Closure;

/**
 * Adds helpers to entities with fields that have to mutated through `POST` after creation.
 */
trait PostMutatorTrait
{

    /**
     * @param string $addPath
     * @param array $data
     * @param string $field
     * @param array $diff
     * @return $this
     */
    private function _addWithPost(string $addPath, array $data, string $field, array $diff)
    {
        if ($this->hasGid()) {
            return $this->_setWithPost($addPath, $data, $field);
        }
        return $this->_set($field, array_merge($this->data[$field] ?? [], array_values($diff)));
    }

    /**
     * @param string $rmPath
     * @param array $data
     * @param string $field
     * @param array|Closure $diff An array to diff, or a filter closure.
     * @return $this
     */
    private function _removeWithPost(string $rmPath, array $data, string $field, $diff)
    {
        if ($this->hasGid()) {
            return $this->_setWithPost($rmPath, $data, $field);
        } elseif (is_array($diff)) {
            return $this->_set($field, array_values(array_diff($this->data[$field] ?? [], $diff)));
        }
        return $this->_set($field, array_values(array_filter($this->data[$field] ?? [], $diff)));
    }

    /**
     * Sets/reloads data via `POST` for existing entities. Otherwise stages a value.
     *
     * @param string $path
     * @param array $data
     * @param string $field
     * @param mixed $value Ignored for existing entities.
     * @return $this
     * @internal
     */
    private function _setWithPost(string $path, array $data, string $field, $value = null)
    {
        if ($this->hasGid()) {
            /** @var array $remote */
            $remote = $this->api->post($path, $data, ['fields' => static::OPT_FIELDS[$field] ?? $field]);
            if (array_key_exists($field, $this->data)) {
                $this->_setField($field, $remote[$field]);
                $this->api->getPool()->add($this);
            }
            return $this;
        }
        return $this->_set($field, $value);
    }
}