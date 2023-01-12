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
    protected array $data = [];

    /**
     * GIDs, keyed by field entry name.
     *
     * @var string[]
     */
    protected array $gids = [];

    /**
     * Field entry names, keyed by GID.
     *
     * @var string[]
     */
    protected array $names = [];

    /**
     * @var Task
     */
    private readonly Task $task;

    /**
     * `[ entry gid => type ]`
     *
     * @var string[]
     */
    protected array $types = [];

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
     * @return void
     * @internal called by an entry to flag a diff
     */
    final public function __set(string $gid, $unused): void
    {
        $this->diff[$gid] = true;
        $this->task->diff['custom_fields'] = true;
    }

    /**
     * @param mixed $unused
     * @return void
     * @internal called by the task to clear diffs
     */
    final public function __unset($unused): void
    {
        $this->diff = [];
        foreach ($this->data as $entry) {
            $entry->diff = [];
        }
    }

    /**
     * @param string $i
     * @param array $data
     * @return void
     */
    protected function _setField(string $i, $data): void
    {
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
    public function getEntry(string $entryIdent): ?FieldEntry
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
    final public function getGids(): array
    {
        return $this->gids;
    }

    /**
     * Values, keyed by entry GID.
     *
     * @return Generator<null|number|string>
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
    final public function getNames(): array
    {
        return $this->names;
    }

    /**
     * @return null[]|number[]|string[]
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
    final public function offsetGet($entryIdent): mixed
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
     * @return void
     */
    final public function offsetSet($entryIdent, $value): void
    {
        $this->getEntry($entryIdent)->setValue($value);
    }

    /**
     * Field entries cannot be "removed" through this. This only sets them to `null`.
     *
     * @param string $entryIdent GID or name
     * @return void
     */
    final public function offsetUnset($entryIdent): void
    {
        $this->offsetSet($entryIdent, null);
    }

    /**
     * @param bool $diff
     * @return array
     */
    public function toArray(bool $diff = false): array
    {
        return !$diff ? parent::toArray() : array_map(
            fn(FieldEntry $entry) => $entry->isEnum() ? $entry->getCurrentOptionGid() : $entry->getValue(),
            array_intersect_key($this->data, $this->diff)
        );
    }

}