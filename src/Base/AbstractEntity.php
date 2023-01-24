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
    protected const DIR = '';

    /**
     * All entity classes must redeclare this to match their `resource_type`.
     */
    public const TYPE = '';

    /**
     * Defines `opt_fields` expressions for lazy-loading/reloading fields.
     *
     * If not set here, the field name is used as-is.
     *
     * @var string[] `fieldName => (expression)`
     */
    protected const OPT_FIELDS = [];

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
    final protected function _get(string $field): mixed
    {
        if (!array_key_exists($field, $this->data) and $this->hasGid()) {
            $this->_reload($field);
        }
        return parent::_get($field);
    }

    /**
     * @param self $dupe
     * @return bool Whether anything was merged.
     * @internal {@link Pool::get()} uses this to fill stubs and prevent object duplication.
     */
    final public function _merge(self $dupe): bool
    {
        $old = $this->toArray();
        $this->data = array_merge($this->data, array_diff_key($dupe->data, $this->diff));
        return $this->toArray() !== $old;
    }

    /**
     * Magic method: `ofResourceSubtypeValue()`
     *
     * @param string $resourceSubtype
     * @return bool
     */
    final protected function _of(string $resourceSubtype): bool
    {
        return $this->_get('resource_subtype') === $resourceSubtype;
    }

    /**
     * @param string $field
     * @return void
     */
    final protected function _reload(string $field): void
    {
        assert($this->hasGid());
        $remote = $this->api->get($this, ['opt_fields' => static::OPT_FIELDS[$field] ?? $field]);
        $this->_setField($field, $remote[$field] ?? null);
        $this->api->getPool()->add($this); // update cache if present
    }

    /**
     * @param array $data
     * @return void
     */
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
    public function getPoolKeys(): array
    {
        return [$this->getGid(), (string)$this];
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
     * @throws RuntimeException Entity was deleted upstream.
     */
    public function reload(): static
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
