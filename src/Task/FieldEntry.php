<?php

namespace Helix\Asana\Task;

use DateTimeInterface;
use Exception;
use Helix\Asana\Base\Data;
use Helix\Asana\CustomField;
use Helix\Asana\CustomField\EnumOption;
use Helix\Asana\Event\Change;
use Helix\Asana\Task\FieldEntry\Date;
use Helix\Asana\User;

/**
 * Represents a task's custom-field value.
 *
 * @immutable When the entry belongs to a {@link Change}
 *
 * @see https://developers.asana.com/reference/custom-fields
 *
 * @method string           getGid              () The custom field's GID.
 * @method string           getResourceSubtype  ()
 *
 * @method null|Date        getDateValue        ()
 * @method null|EnumOption  getEnumValue        () For subtype `enum`
 * @method EnumOption[]     getMultiEnumValues  () For subtype `multi_enum`
 * @method null|number      getNumberValue      () For subtype `number`
 * @method User[]           getPeopleValue      () For subtype `people`
 * @method null|string      getTextValue        () For subtype `text`
 *
 * @method $this            setNumberValue      (?number $number)
 * @method $this            setPeopleValue      (User[] $people)
 * @method $this            setTextValue        (?string $text)
 *
 * @method User[]           selectPeopleValue   (callable $filter) `fn( User $user ): bool`
 * @method EnumOption[]     selectMultiEnumValues(callable $filter) `fn( EnumOption $option): bool`
 *
 * @method bool ofDate      ()
 * @method bool ofEnum      ()
 * @method bool ofMultiEnum ()
 * @method bool ofNumber    ()
 * @method bool ofPeople    ()
 * @method bool ofText      ()
 */
class FieldEntry extends Data
{

    protected const MAP = [
        'date_value' => Date::class,
        'enum_value' => EnumOption::class,
        'multi_enum_values' => [EnumOption::class],
        'people_value' => [User::class]
    ];

    /**
     * @var Change|FieldEntries
     */
    private readonly Change|FieldEntries $caller;

    /**
     * The `<resource_subtype>_value(s)` data key.
     *
     * @var string
     */
    protected readonly string $key;

    /**
     * @param Change|FieldEntries $caller
     * @param array $data
     */
    public function __construct(Change|FieldEntries $caller, array $data = [])
    {
        $this->caller = $caller;
        $key = "{$data['resource_subtype']}_value";
        $this->key = array_key_exists($key, $data) ? $key : "{$key}s"; // pluralize to "values";
        parent::__construct($caller, $data);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->getDisplayValue();
    }

    /**
     * Values are immutable when the entry belongs to a {@link Change}
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    protected function _set(string $field, $value): static
    {
        assert($this->caller instanceof FieldEntries);
        parent::_set($field, $value);
        $this->caller->diff[$this->data['gid']] = true;
        $this->caller->getTask()->diff['custom_fields'] = true;
        return $this;
    }

    /**
     * Strips Asana's redundant {@link CustomField} and {@link EnumOption} data.
     *
     * @param array $data
     * @return void
     */
    protected function _setData(array $data): void
    {
        if (isset($data['resource_type'])) { // sentinel for bloated remote
            $data = array_intersect_key($data, array_flip([
                'gid',
                'display_value',
                'resource_subtype',
                $this->key,
            ]));
        }
        parent::_setData($data);
    }

    /**
     * @return CustomField
     */
    public function getCustomField(): CustomField
    {
        return $this->api->getCustomField($this->data['gid']);
    }

    /**
     * Determines the human-readable value in a future-proof way.
     *
     * If a new value is set, and the entry is for a mapped type, the updated display value is returned.
     *
     * When the entry is for an unknown type, this attempts to determine it,
     * but may fall back on the upstream `display_value`,
     * which won't be correct if the value was changed.
     *
     * If you're getting incorrect display values for object-like entries,
     * you should extend this class and add the new type/s to {@link FieldEntry::MAP}.
     *
     * @return null|string
     */
    public function getDisplayValue(): ?string
    {
        $raw = $this->getRawValue();
        if ($raw === null) { // nothing
            return null;
        } elseif (isset($raw['gid'])) { // entity-like
            return $raw['name'];
        } elseif (is_array($raw)) {
            return array_is_list($raw)
                ? implode(', ', array_column($raw, 'name')) // entity-list-like
                : $this->data['display_value']; // unmapped data, can't determine
        } else { // scalars and stringable data
            return (string)$raw;
        }
    }

    /**
     * @return null|number|string|array|Data|Data[]
     */
    final public function getRawValue()
    {
        return $this->data[$this->key];
    }

    /**
     * @param null|string|DateTimeInterface|Date $date
     * @return $this
     * @throws Exception
     */
    public function setDateValue($date): static
    {
        return $this->_set('date_value', isset($date)
            ? ($date instanceof Date ? $date : $this->api->factory(Date::class, $this, $date))
            : null
        );
    }

    /**
     * @param null|string|EnumOption $value GID, name, or instance
     * @return $this
     */
    public function setEnumValue($value): static
    {
        return $this->_set('enum_value', isset($value)
            ? ($value instanceof EnumOption ? $value : $this->getCustomField()->getEnumOption($value))
            : null
        );
    }

    /**
     * @param array<string|EnumOption> $values GIDs, names, or instances
     * @return $this
     */
    public function setMultiEnumValues(array $values): static
    {
        return $this->_set('multi_enum_values', array_map(fn($value) => isset($value)
                ? ($value instanceof EnumOption ? $value : $this->getCustomField()->getEnumOption($value))
                : null,
                $values)
        );
    }

    /**
     * Calls the respective setter depending on the entry's type.
     *
     * Enum values can be set using {@link EnumOption} instances, names, or GIDs.
     *
     * Dates can be set using {@link Date} instances, or date-like strings.
     *
     * All other types must be set with their full representations.
     *
     * Unmapped objects and object-lists can be given as associative arrays.
     *
     * @param mixed $value
     * @return $this
     */
    public function setValue($value): static
    {
        if ($this->ofDate()) {
            return $this->setDateValue($value);
        }
        if ($this->ofEnum()) {
            return $this->setEnumValue($value);
        }
        if ($this->ofMultiEnum()) {
            return $this->setMultiEnumValues($value);
        }
        return $this->_set($this->key, $value);
    }
}
