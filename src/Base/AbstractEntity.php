<?php

namespace Helix\Asana\Base;

use RuntimeException;

/**
 * A resource with a GID.
 *
 * @see https://developers.asana.com/docs/object-hierarchy
 */
abstract class AbstractEntity extends Data
{

    /**
     * All entity classes must redeclare this to match their REST directory.
     */
    const DIR = '';

    /**
     * All entity classes must redeclare this to match their `resource_type`.
     */
    const TYPE = '';

    /**
     * Defines `opt_fields` expressions for lazy-loading/reloading fields.
     *
     * If not set here, the field name is used as-is.
     *
     * @var string[] `fieldName => (expression)`
     */
    const OPT_FIELDS = [];

    /**
     * @param self $entity
     * @return bool
     * @internal pool
     */
    final public function __merge(self $entity): bool
    {
        $old = $this->toArray();
        $this->data = array_merge($this->data, array_diff_key($entity->data, $this->diff));
        return $this->toArray() !== $old;
    }

    /**
     * The entity's canonical REST path.
     *
     * @return string
     */
    final public function __toString(): string
    {
        return static::DIR . '/' . $this->getGid();
    }

    /**
     * Lazy-loads missing fields.
     *
     * @param string $field
     * @return mixed
     */
    protected function _get(string $field)
    {
        if (!array_key_exists($field, $this->data) and $this->hasGid()) {
            $this->_reload($field);
        }
        return parent::_get($field);
    }

    /**
     * @param string $field
     */
    protected function _reload(string $field): void
    {
        assert($this->hasGid());
        $remote = $this->api->get($this, ['opt_fields' => static::OPT_FIELDS[$field] ?? $field]);
        $this->_setField($field, $remote[$field] ?? null);
        $this->api->getPool()->add($this);
    }

    protected function _setData(array $data): void
    {
        // meaningless once the entity is being created. it's constant.
        unset($data['resource_type'], $data['type']);

        parent::_setData($data);
    }

    /**
     * @return null|string
     */
    final public function getGid(): ?string
    {
        return $this->data['gid'] ?? null;
    }

    /**
     * Identifiers the entity is pooled with.
     *
     * @return string[]
     */
    public function getPoolKeys()
    {
        return [$this->getGid(), (string)$this];
    }

    /**
     * All entity classes must declare a `TYPE` constant.
     *
     * @return string
     */
    final public function getResourceType(): string
    {
        return static::TYPE;
    }

    /**
     * @return bool
     */
    final public function hasGid(): bool
    {
        return isset($this->data['gid']);
    }

    /**
     * Fully reloads the entity from Asana.
     *
     * @return $this
     */
    public function reload()
    {
        assert($this->hasGid());
        $remote = $this->api->get($this, ['opt_expand' => 'this']);
        if (!isset($remote['gid'])) { // deleted?
            $this->api->getPool()->remove($this->getPoolKeys());
            throw new RuntimeException("{$this} was deleted upstream.");
        }
        $this->_setData($remote);
        $this->api->getPool()->add($this);
        return $this;
    }

}