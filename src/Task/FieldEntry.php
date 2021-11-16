<?php

namespace Helix\Asana\Task;

use Helix\Asana\Base\Data;
use Helix\Asana\CustomField;
use Helix\Asana\Event\Change;

/**
 * A task's custom field entry.
 *
 * Enum values are set by option GID (recommended) or name.
 *
 * @method string getGid    () The custom field's GID.
 * @method string getName   ()
 * @method string getType   ()
 */
class FieldEntry extends Data
{

    /**
     * @var Change|FieldEntries
     */
    protected $caller;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @param Change|FieldEntries $caller
     * @param array $data
     */
    public function __construct($caller, array $data = [])
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
     * Strips Asana's beefy data array down to what we need.
     *
     * @param array $data
     */
    protected function _setData(array $data): void
    {
        if (isset($data['resource_subtype'])) { // sentinel for bloat
            $tiny = array_intersect_key($data, array_flip([
                'gid',
                'name',
                'type', // "deprecated"
                'enum_options',
                "{$data['type']}_value"
            ]));
            if (isset($tiny['enum_options'])) {
                $tiny['enum_options'] = array_map(function (array $option) {
                    return ['gid' => $option['gid'], 'name' => $option['name']];
                }, $tiny['enum_options']);
                if (isset($tiny['enum_value'])) {
                    $tiny['enum_value'] = ['gid' => $tiny['enum_value']['gid']];
                }
            }
            $data = $tiny;
        }
        parent::_setData($data);
    }

    /**
     * Resolves an enum option's GID from a human-readable label.
     *
     * @param null|string $value
     * @return null|string
     */
    protected function _toEnumOptionGid(?string $value)
    {
        return $this->getEnumOptionValues()[$value] ?? $value;
    }

    /**
     * The enum value's GID.
     *
     * @return null|string
     */
    final public function getCurrentOptionGid(): ?string
    {
        return $this->data['enum_value']['gid'] ?? null;
    }

    /**
     * Resolves the enum value's human-readable label (the name).
     *
     * @return null|string
     */
    final public function getCurrentOptionName(): ?string
    {
        if ($optionGid = $this->getCurrentOptionGid()) {
            return $this->getEnumOptionNames()[$optionGid];
        }
        return null;
    }

    /**
     * @return CustomField
     */
    public function getCustomField()
    {
        return $this->api->getCustomField($this->data['gid']);
    }

    /**
     * Enum option names keyed by GID.
     *
     * @return string[]
     */
    final public function getEnumOptionNames()
    {
        static $names = []; // shared
        $gid = $this->data['gid'];
        return $names[$gid] ?? $names[$gid] = array_column($this->data['enum_options'], 'name', 'gid');
    }

    /**
     * Enum option GIDs keyed by name.
     *
     * @return string[]
     */
    final public function getEnumOptionValues()
    {
        static $values = []; // shared
        $gid = $this->data['gid'];
        return $values[$gid] ?? $values[$gid] = array_column($this->data['enum_options'], 'gid', 'name');
    }

    /**
     * Resolves to the human-readable value.
     *
     * @return null|number|string
     */
    final public function getValue()
    {
        if ($this->isEnum()) {
            return $this->getCurrentOptionName();
        }
        return $this->data["{$this->getType()}_value"];
    }

    /**
     * @return bool
     */
    final public function isEnum(): bool
    {
        return $this->getType() === CustomField::TYPE_ENUM;
    }

    /**
     * @return bool
     */
    final public function isNumber(): bool
    {
        return $this->getType() === CustomField::TYPE_NUMBER;
    }

    /**
     * @return bool
     */
    final public function isText(): bool
    {
        return $this->getType() === CustomField::TYPE_TEXT;
    }

    /**
     * Sets the human-readable value.
     *
     * Values are immutable when the entry belongs to a {@link Change}
     *
     * @param null|number|string $value
     * @return $this
     */
    final public function setValue($value)
    {
        if ($this->caller instanceof FieldEntries) {
            $type = $this->data['type'];
            $this->diff["{$type}_value"] = true;
            $this->caller->__set($this->data['gid'], true);
            if ($type === CustomField::TYPE_ENUM) {
                $this->data['enum_value']['gid'] = $this->_toEnumOptionGid($value);
            } else {
                $this->data["{$type}_value"] = $value;
            }
        }
        return $this;
    }

}