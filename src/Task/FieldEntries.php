<?php

namespace Helix\Asana\Task;

use ArrayAccess;
use Countable;
use Generator;
use Helix\Asana\Base\Data;
use Helix\Asana\Task;
use IteratorAggregate;

/**
 * Acts as a container for a task's custom-field values.
 *
 * Field array-access is by GID (recommended) or name.
 */
class FieldEntries extends Data implements ArrayAccess, Countable, IteratorAggregate
{

    /**
     * Field entries, keyed by GID.
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
     * @param array $data Enumerated {@link FieldEntry} data.
     */
    public function __construct(Task $task, array $data = [])
    {
        $this->task = $task;
        parent::__construct($task, $data);
    }

    /**
     * Overridden to use {@link $data} for each {@link FieldEntry}.
     *
     * @param string $i
     * @param array $data
     * @return void
     */
    protected function _setField(string $i, $data): void
    {
        $entry = $this->api->factory(FieldEntry::class, $this, $data);
        $this->data[$entry->getGid()] = $entry;
    }

    /**
     * @return int
     */
    final public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return FieldEntry[]
     */
    public function getEntries(): array
    {
        return $this->data;
    }

    /**
     * @param string $ident GID or name
     * @return null|FieldEntry
     */
    public function getEntry(string $ident): ?FieldEntry
    {
        foreach ($this->data as $entry) {
            if ($entry->getGid() === $ident or $entry->getCustomField()->getName() === $ident) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Values, keyed by entry GID.
     *
     * @return Generator<string,null|number|string>
     */
    public function getIterator(): Generator
    {
        foreach ($this->data as $gid => $field) {
            yield $gid => $field->getValue();
        }
    }

    /**
     * @return Task
     */
    public function getTask(): Task
    {
        return $this->task;
    }

    /**
     * @return array<string,null|number|string>
     */
    final public function getValues(): array
    {
        return iterator_to_array($this);
    }

    /**
     * Does a `null` check on an entry's value.
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
        return $this->getEntry($entryIdent)?->getValue();
    }

    /**
     * Sets an entry's human-readable value. The entry must exist.
     *
     * @param string $entryIdent GID or name
     * @param null|number|string $value This can be an enum option GID.
     * @return void
     */
    final public function offsetSet($entryIdent, $value): void
    {
        $this->getEntry($entryIdent)?->setValue($value);
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
     * Overriden to prepare entry diffs for task upsert.
     *
     * When upserting a task, custom fields must be given as their machine-values, keyed by GID.
     *
     * @param bool $diff
     * @return array
     */
    public function toArray(bool $diff = false): array
    {
        return !$diff ? parent::toArray() : array_map(
            fn(FieldEntry $entry) => $entry->ofEnum() ? $entry->getEnumValue()?->getGid() : $entry->getValue(),
            array_intersect_key($this->data, $this->diff)
        );
    }

}
