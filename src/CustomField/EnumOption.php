<?php

namespace Helix\Asana\CustomField;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CreateTrait;
use Helix\Asana\Base\AbstractEntity\UpdateTrait;
use Helix\Asana\CustomField;
use Helix\Asana\Task\FieldEntry;

/**
 * A custom field enum option.
 *
 * > :warning:
 * > This class cannot lazy-load, since there is no GET endpoint.
 * > {@link FieldEntry} eager-loads via {@link CustomField::getEnumOptions()}
 *
 * Enum options cannot be deleted.
 *
 * @see https://developers.asana.com/docs/create-an-enum-option
 * @see https://developers.asana.com/docs/enum-option
 *
 * @method string   getColor    ()
 * @method bool     isEnabled   ()
 * @method string   getName     ()
 *
 * @method $this    setColor    (string $color)
 * @method $this    setEnabled  (bool $enabled)
 * @method $this    setName     (string $name)
 */
class EnumOption extends AbstractEntity
{

    use CreateTrait {
        create as private _create;
    }
    use UpdateTrait;

    final protected const DIR = 'enum_options';
    final public const TYPE = 'enum_option';

    /**
     * @var CustomField|FieldEntry
     */
    private readonly CustomField|FieldEntry $caller;

    /**
     * @param CustomField|FieldEntry $caller
     * @param array $data
     */
    public function __construct(CustomField|FieldEntry $caller, array $data = [])
    {
        $this->caller = $caller;
        parent::__construct($caller, $data);
    }

    /**
     * @return CustomField
     */
    final protected function _getParentNode(): CustomField
    {
        return $this->getCustomField();
    }

    /**
     * @return $this
     */
    public function create(): static
    {
        $this->_create();
        $this->getCustomField()->_reload('enum_options');
        return $this;
    }

    /**
     * @return CustomField
     */
    public function getCustomField(): CustomField
    {
        return $this->caller instanceof FieldEntry ? $this->caller->getCustomField() : $this->caller;
    }

    /**
     * Move above another option.
     *
     * @see https://developers.asana.com/docs/reorder-a-custom-fields-enum
     *
     * @param EnumOption $option
     * @return $this
     */
    public function moveAbove(EnumOption $option): static
    {
        $field = $this->getCustomField();
        $this->api->post("{$field}/enum_options/insert", [
            'before_enum_option' => $option->getGid(),
            'enum_option' => $this->getGid()
        ]);
        $field->_reload('enum_options');
        return $this;
    }

    /**
     * Move below another option.
     *
     * @see https://developers.asana.com/docs/reorder-a-custom-fields-enum
     *
     * @param EnumOption $option
     * @return $this
     */
    public function moveBelow(EnumOption $option): static
    {
        $field = $this->getCustomField();
        $this->api->post("{$field}/enum_options//insert", [
            'after_enum_option' => $option->getGid(),
            'enum_option' => $this->getGid()
        ]);
        $field->_reload('enum_options');
        return $this;
    }

    /**
     * Make the option first.
     *
     * @return $this
     */
    public function moveFirst(): static
    {
        $first = $this->getCustomField()->getEnumOptions()[0];
        if ($first !== $this) {
            $this->moveAbove($first);
        }
        return $this;
    }

    /**
     * Make the option last.
     *
     * @return $this
     */
    public function moveLast(): static
    {
        $options = $this->getCustomField()->getEnumOptions();
        $last = $options[count($options) - 1];
        if ($last !== $this) {
            $this->moveBelow($last);
        }
        return $this;
    }
}
