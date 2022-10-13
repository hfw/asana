<?php

namespace Helix\Asana\Task;

use ArrayAccess;
use Countable;
use Generator;
use Helix\Asana\Base\Data;
use Helix\Asana\Task;
use IteratorAggregate;

/**
 * Custom field value adapter for tasks.
 *
 * Field access is by GID (recommended) or name.
 */
class FieldEntries extends Data implements ArrayAccess, Countable, IteratorAggregate
{

    /**
     * Field entries, keyed by gid.
     *
     * @var FieldEntry[]
     */
    protected $data = [];

    /**
     * GIDs, keyed by field entry name.
     *
     * @var string[]
     */
    protected $gids = [];

    /**
     * Field entry names, keyed by GID.
     *
     * @var string[]
     */
    protected $names = [];

    /**
     * @var Task
     */
    protected $task;

    /**
     * `[ entry gid => type ]`
     *
     * @var string[]
     */
    protected $types = [];

    /**
     * @param Task $task
     * @param array $data
     */
    public function __construct(Task $task, array $data = [])
    {
        $this->task = $task;
        parent::__construct($task, $data);
    }

    /**
     * @param string $gid
     * @param mixed $unused
     * @internal called by an entry
     */
    final public function __set(string $gid, $unused): void
    {
        $this->diff[$gid] = true;
        $this->task->diff['custom_fields'] = true;
    }

    /**
     * @param mixed $unused
     * @internal called by the task
     */
    final public function __unset($unused): void
    {
        $this->diff = [];
        foreach ($this->data as $entry) {
            $entry->diff = [];
        }
    }

    /**
     * Inflates.
     *
     * @param string $i
     * @param array $data
     */
    protected function _setField(string $i, $data): void
    {
        /** @var FieldEntry $entry */
        $entry = $this->api->factory($this, FieldEntry::class, $data);
        $gid = $entry->getGid();
        $name = $entry->getName();
        $this->data[$gid] = $entry;
        $this->gids[$name] = $gid;
        $this->names[$gid] = $name;
    }

    /**
     * Resolves ambiguous entry identifiers to GIDs.
     *
     * @param string $entryIdent GID or name
     * @return string
     */
    protected function _toGid(string $entryIdent): string
    {
        return $this->gids[$entryIdent] ?? $entryIdent;
    }

    /**
     * @return int
     */
    final public function count(): int
    {
        return count($this->data);
    }

    /**
     * @param string $entryIdent GID or name
     * @return null|FieldEntry
     */
    public function getEntry(string $entryIdent)
    {
        return $this->data[$this->_toGid($entryIdent)] ?? null;
    }

    /**
     * @param string $name
     * @return string
     */
    final public function getGid(string $name): string
    {
        return $this->gids[$name];
    }

    /**
     * @return string[]
     */
    final public function getGids()
    {
        return $this->gids;
    }

    /**
     * Values, keyed by entry GID.
     *
     * @return Generator
     */
    public function getIterator(): Generator
    {
        foreach ($this->data as $gid => $field) {
            yield $gid => $field->getValue();
        }
    }

    /**
     * @param string $entryGid
     * @return string
     */
    final public function getName(string $entryGid): string
    {
        return $this->names[$entryGid];
    }

    /**
     * @return string[]
     */
    final public function getNames()
    {
        return $this->names;
    }

    /**
     * @return array
     */
    final public function getValues(): array
    {
        return iterator_to_array($this);
    }

    /**
     * Whether an entry exists, regardless of its value.
     *
     * @param string $entryIdent GID or name
     * @return bool
     */
    final public function hasEntry(string $entryIdent): bool
    {
        return $this->getEntry($entryIdent) !== null;
    }

    /**
     * Does a `null` check on an entry's value.
     *
     * To determine whether the field actually exists, use {@link hasEntry()} instead.
     *
     * @param string $entryIdent GID or name
     * @return bool
     */
    final public function offsetExists($entryIdent): bool
    {
        return $this->offsetGet($entryIdent) !== null;
    }

    /**
     * An entry's human-readable value.
     *
     * @param string $entryIdent GID or name
     * @return null|number|string Also returns `null` if there is no such entry.
     */
    #[\ReturnTypeWillChange]
    final public function offsetGet($entryIdent)
    {
        if ($entry = $this->getEntry($entryIdent)) {
            return $entry->getValue();
        }
        return null;
    }

    /**
     * Sets an entry's human-readable value. The entry must exist.
     *
     * @param string $entryIdent GID or name
     * @param null|number|string $value
     */
    final public function offsetSet($entryIdent, $value): void
    {
        $this->getEntry($entryIdent)->setValue($value);
    }

    /**
     * Field entries cannot be "removed" through this. This only sets them to `null`.
     *
     * @param string $entryIdent GID or name
     */
    final public function offsetUnset($entryIdent): void
    {
        $this->offsetSet($entryIdent, null);
    }

    public function toArray(bool $diff = false): array
    {
        if ($diff) {
            return array_map(function (FieldEntry $entry) {
                if ($entry->isEnum()) {
                    return $entry->getCurrentOptionGid();
                }
                return $entry->getValue();
            }, array_intersect_key($this->data, $this->diff));
        }
        return parent::toArray();
    }

}