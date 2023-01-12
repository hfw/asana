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
    private readonly Change|FieldEntries $caller;

    /**
     * @var array
     */
    protected array $data = [];

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
     * Strips Asana's beefy data array down to what we need.
     *
     * @param array $data
     * @return void
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
                $tiny['enum_options'] = array_map(
                    fn(array $option) => ['gid' => $option['gid'], 'name' => $option['name']],
                    $tiny['enum_options']
                );
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
    final protected function _toEnumOptionGid(?string $value): ?string
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
    public function getCustomField(): CustomField
    {
        return $this->api->getCustomField($this->data['gid']);
    }

    /**
     * Enum option names keyed by GID.
     *
     * @return string[]
     */
    final public function getEnumOptionNames(): array
    {
        static $names = []; // shared
        return $names[$this->data['gid']] ??= array_column($this->data['enum_options'], 'name', 'gid');
    }

    /**
     * Enum option GIDs keyed by name.
     *
     * @return string[]
     */
    final public function getEnumOptionValues(): array
    {
        static $values = []; // shared
        return $values[$this->data['gid']] ??= array_column($this->data['enum_options'], 'gid', 'name');
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
    final public function setValue($value): static
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