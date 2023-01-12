<?php

namespace Helix\Asana\CustomField;

use Helix\Asana\CustomField;

/**
 * Adds custom field settings to an entity.
 *
 * @method CustomField[]    getCustomFieldSettings  ()
 * @method CustomField[]    selectCustomFields      (callable $filter) `fn( CustomField $field ): bool`
 */
trait FieldSettingsTrait
{

    /**
     * @return CustomField[]
     */
    public function getCustomFields(): array
    {
        return array_column($this->getCustomFieldSettings(), 'custom_field');
    }

}