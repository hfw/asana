<?php

namespace Helix\Asana\Base\AbstractEntity;

use Closure;

/**
 * Adds helpers to entities with fields that have to mutated through `POST` after creation.
 */
trait PostMutatorTrait
{

    /**
     * For existing entities this *adds references* to other entities via the given `POST` path.
     *
     * Stages otherwise.
     *
     * @param string $addPath
     * @param array $postData
     * @param string $field
     * @param array $diff For staging.
     * @return $this
     */
    protected function _addWithPost(string $addPath, array $postData, string $field, array $diff): static
    {
        if ($this->hasGid()) {
            return $this->_setWithPost($addPath, $postData, $field);
        }
        return $this->_set($field, array_merge($this->data[$field] ?? [], array_values($diff)));
    }

    /**
     * For existing entities this *removes references* to other entities via the given `POST` path.
     *
     * Stages otherwise.
     *
     * @param string $rmPath
     * @param array $postData
     * @param string $field
     * @param array|Closure $diff For staging. An array, or a filter closure: `fn($eachValue):bool`
     * @return $this
     */
    protected function _removeWithPost(string $rmPath, array $postData, string $field, array|Closure $diff): static
    {
        if ($this->hasGid()) {
            return $this->_setWithPost($rmPath, $postData, $field);
        } elseif (is_array($diff)) {
            return $this->_set($field, array_values(array_diff($this->data[$field] ?? [], $diff)));
        }
        return $this->_set($field, array_values(array_filter($this->data[$field] ?? [], $diff)));
    }

    /**
     * For existing entities this *alters one or more references* to other entities via the given `POST` path.
     *
     * Stages otherwise.
     *
     * @param string $postPath
     * @param array $postData
     * @param string $field
     * @param mixed $value For staging the final value.
     * @return $this
     * @internal
     */
    protected function _setWithPost(string $postPath, array $postData, string $field, $value = null): static
    {
        if ($this->hasGid()) {
            $remote = $this->api->post($postPath, $postData, ['fields' => static::OPT_FIELDS[$field] ?? $field]);
            if (array_key_exists($field, $this->data)) {
                $this->_setField($field, $remote[$field]);
                $this->api->getPool()->add($this);
            }
            return $this;
        }
        return $this->_set($field, $value);
    }
}