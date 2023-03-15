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
 *
 * @method FieldEntry[] selectEntries (callable $filter) `fn( FieldEntry $entry ): bool`
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
     * @param string $i Field entry enumeration. This is ignored, since entries are keyed by GID.
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
     * Human values, keyed by entry GID.
     *
     * @return Generator<string,null|string>
     */
    public function getIterator(): Generator
    {
        foreach ($this->data as $gid => $field) {
            yield $gid => $field->getDisplayValue();
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
     * @param string $ident
     * @return bool
     */
    final public function hasEntry(string $ident): bool
    {
        return $this->getEntry($ident) !== null;
    }

    /**
     * An entry's display-value.
     *
     * @param string $entryIdent GID or name
     * @return null|string Also returns `null` if there is no such entry.
     */
    final public function offsetGet($entryIdent): mixed
    {
        return $this->getEntry($entryIdent)?->getDisplayValue();
    }

    /**
     * Sets an entry's value. The entry must exist.
     *
     * {@see FieldEntry::setValue()}
     *
     * @param string $entryIdent GID or name
     * @param mixed $value
     * @return void
     */
    final public function offsetSet($entryIdent, $value): void
    {
        $this->getEntry($entryIdent)->setValue($value);
    }

    /**
     * Overridden to prepare entry diffs for task upsert.
     *
     * @param bool $diff
     * @return array
     */
    public function toArray(bool $diff = false): array
    {
        if (!$diff) {
            return parent::toArray();
        }
        $array = [];
        foreach (array_intersect_key($this->data, $this->diff) as $gid => $entry) {
            $raw = $entry->getRawValue();
            if (isset($raw['gid'])) { //entity-like
                $array[$gid] = $raw['gid'];
            } elseif (is_array($raw) and array_is_list($raw)) { // entity-list-like
                $array[$gid] = array_column($raw, 'gid');
            } elseif ($raw instanceof Data) {
                $array[$gid] = $raw->toArray(); // mapped data
            } else {
                $array[$gid] = $raw; // as-is
            }
        }
        return $array;
    }

}
