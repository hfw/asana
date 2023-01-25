<?php

namespace Helix\Asana\Task;

use Helix\Asana\Base\Data;
use Helix\Asana\CustomField;
use Helix\Asana\CustomField\EnumOption;
use Helix\Asana\Event\Change;

/**
 * Represents a task's custom-field value.
 *
 * Enum values are set by option GID (recommended) or name.
 *
 * @method string           getGid                () The custom field's GID.
 * @method string           getResourceSubtype    ()
 * @method null|EnumOption  getEnumValue          ()
 *
 * @method bool ofEnum      ()
 * @method bool ofNumber    ()
 * @method bool ofText      ()
 */
class FieldEntry extends Data
{

    protected const MAP = [
        'enum_value' => EnumOption::class
    ];

    /**
     * @var Change|FieldEntries
     */
    private readonly Change|FieldEntries $caller;

    /**
     * @param Change|FieldEntries $caller
     * @param array $data
     */
    public function __construct(Change|FieldEntries $caller, array $data = [])
    {
        $this->caller = $caller;
        parent::__construct($caller, $data);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->getValue();
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
                'resource_subtype',
                "{$data['resource_subtype']}_value"
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
     * Resolves the human-readable value.
     *
     * @return null|number|string
     */
    final public function getValue()
    {
        if ($this->ofEnum()) {
            return $this->getEnumValue()?->getName();
        }
        return $this->data["{$this->data['resource_subtype']}_value"];
    }

    /**
     * Sets the human-readable value.
     *
     * Values are immutable when the entry belongs to a {@link Change}
     *
     * @param null|number|string $value This can be an enum option GID.
     * @return $this
     */
    final public function setValue($value): static
    {
        if ($this->caller instanceof FieldEntries) {
            if ($this->ofEnum()) {
                $this->data['enum_value'] = isset($value)
                    ? $this->getCustomField()->getEnumOption($value)
                    : null;
            } else {
                $this->data["{$this->data['resource_subtype']}_value"] = $value;
            }
            $this->diff["{$this->data['resource_subtype']}_value"] = true;
            $this->caller->diff[$this->data['gid']] = true;
            $this->caller->getTask()->diff['custom_fields'] = true;
        }
        return $this;
    }

}
