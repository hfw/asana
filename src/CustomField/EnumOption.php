<?php

namespace Helix\Asana\CustomField;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CreateTrait;
use Helix\Asana\Base\AbstractEntity\UpdateTrait;
use Helix\Asana\CustomField;

/**
 * A custom field enum option.
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
class EnumOption extends AbstractEntity {

    use CreateTrait {
        create as private _create;
    }
    use UpdateTrait;

    const DIR = 'enum_options';
    const TYPE = 'enum_option';

    /**
     * @var CustomField
     */
    protected $customField;

    /**
     * @param CustomField $field
     * @param array $data
     */
    public function __construct (CustomField $field, array $data = []) {
        $this->customField = $field;
        parent::__construct($field, $data);
    }

    /**
     * @return $this
     */
    public function create () {
        $this->_create();
        $this->customField->_reload('enum_options'); // safe. the options are pooled.
        return $this;
    }

    /**
     * @return CustomField
     */
    public function getCustomField () {
        return $this->customField;
    }

    /**
     * @return CustomField
     */
    final protected function getParentNode () {
        return $this->customField;
    }

    /**
     * Move above another option.
     *
     * @see https://developers.asana.com/docs/reorder-a-custom-fields-enum
     *
     * @param EnumOption $option
     * @return $this
     */
    public function moveAbove (EnumOption $option) {
        $this->api->post("{$this->customField}/enum_options/insert", [
            'before_enum_option' => $option->getGid(),
            'enum_option' => $this->getGid()
        ]);
        $this->customField->_reload('enum_options'); // safe. the options are pooled.
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
    public function moveBelow (EnumOption $option) {
        $this->api->post("{$this->customField}/enum_options//insert", [
            'after_enum_option' => $option->getGid(),
            'enum_option' => $this->getGid()
        ]);
        $this->customField->_reload('enum_options'); // safe. the options are pooled.
        return $this;
    }

    /**
     * Make the option first.
     *
     * @return $this
     */
    public function moveFirst () {
        $first = $this->customField->getEnumOptions()[0];
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
    public function moveLast () {
        $options = $this->customField->getEnumOptions();
        $last = $options[count($options) - 1];
        if ($last !== $this) {
            $this->moveBelow($last);
        }
        return $this;
    }
}