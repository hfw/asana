<?php

namespace Helix\Asana\CustomField;

use Helix\Asana\Base\Data;
use Helix\Asana\CustomField;

/**
 * A custom field setting.
 *
 * @see https://developers.asana.com/docs/get-a-projects-custom-fields
 * @see https://developers.asana.com/docs/get-a-portfolios-custom-fields
 * @see https://developers.asana.com/docs/custom-field-setting
 *
 * @method CustomField  getCustomField  ()
 * @method bool         isImportant     ()
 */
class FieldSetting extends Data {

    const TYPE = 'custom_field_setting';

    protected const MAP = [
        'custom_field' => CustomField::class,
    ];

    protected function _setData (array $data): void {
        // these are the only fields that matter.
        parent::_setData([
            'custom_field' => $data['custom_field'],
            'is_important' => $data['is_important']
        ]);
    }

}