<?php

namespace Helix\Asana;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CrudTrait;
use Helix\Asana\Base\AbstractEntity\PostMutatorTrait;
use Helix\Asana\CustomField\EnumOption;
use Helix\Asana\Task\FieldEntries;

/**
 * A custom field.
 *
 * See {@link FieldEntries} for getting {@link Task} values.
 *
 * @see https://developers.asana.com/docs/asana-custom-fields
 * @see https://developers.asana.com/docs/custom-field
 *
 * @see Workspace::newCustomField()
 *
 * @method $this        setResourceSubtype      (string $type)          @depends create-only, see the subtype constants
 * @method $this        setWorkspace            (Workspace $workspace)  @depends create-only, no getter
 *
 * @method User         getCreatedBy            ()
 * @method null|string  getCurrencyCode         () ISO 4217
 * @method null|string  getCustomLabel          () `null` unless `format` is `custom`
 * @method string       getCustomLabelPosition  () See the position constants.
 * @method string       getDescription          ()
 * @method bool         isEnabled               ()
 * @method EnumOption[] getEnumOptions          ()
 * @method string       getFormat               () See the format constants.
 * @method string       getName                 ()
 * @method int          getPrecision            ()
 * @method string       getResourceSubtype      ()
 *
 * @method $this        setCurrencyCode         (string $iso4217) Requires `subtype=number`, `format=currency`
 * @method $this        setCustomLabel          (string $label) Requires `format=custom`
 * @method $this        setCustomLabelPosition  (string $position) See the position constants.
 * @method $this        setDescription          (string $text)
 * @method $this        setFormat               (string $format) See the format constants.
 * @method $this        setName                 (string $name)
 * @method $this        setPrecision            (int $precision)
 *
 * @method EnumOption[] selectEnumOptions       (callable $filter) `fn( EnumOption $option ): bool`
 *
 * @method bool ofDate      ()
 * @method bool ofEnum      ()
 * @method bool ofMultiEnum ()
 * @method bool ofNumber    ()
 * @method bool ofPeople    ()
 * @method bool ofText      ()
 */
class CustomField extends AbstractEntity
{

    use CrudTrait;
    use PostMutatorTrait;

    final protected const DIR = 'custom_fields';
    final public const TYPE = 'custom_field';

    final public const FORMAT_CURRENCY = 'currency';
    final public const FORMAT_CUSTOM = 'custom';
    final public const FORMAT_IDENTIFIER = 'identifier';
    final public const FORMAT_NONE = 'none';
    final public const FORMAT_PERCENTAGE = 'percentage';

    final public const POSITION_PREFIX = 'prefix';
    final public const POSITION_SUFFIX = 'suffix';

    protected const MAP = [
        'created_by' => User::class,
        'enum_options' => [EnumOption::class],
    ];

    /**
     * @return null
     */
    final protected function _getParentNode()
    {
        return null;
    }

    /**
     * @param string $ident GID or name.
     * @return null|EnumOption
     */
    public function getEnumOption(string $ident): ?EnumOption
    {
        foreach ($this->getEnumOptions() as $option) {
            if ($option->getGid() === $ident or $option->getName() === $ident) {
                return $option;
            }
        }
        return null;
    }

    /**
     * @return bool
     */
    final public function hasNotificationsEnabled(): bool
    {
        return $this->_is('has_notifications_enabled');
    }

    /**
     * @return bool
     */
    final public function isCurrency(): bool
    {
        return $this->getFormat() === self::FORMAT_CURRENCY;
    }

    /**
     * @return bool
     */
    final public function isGlobalToWorkspace(): bool
    {
        return $this->_is('is_global_to_workspace');
    }

    /**
     * @return bool
     */
    final public function isIdentifier(): bool
    {
        return $this->getFormat() === self::FORMAT_IDENTIFIER;
    }

    /**
     * @return bool
     */
    final public function isPercentage(): bool
    {
        return $this->getFormat() === self::FORMAT_PERCENTAGE;
    }

    /**
     * @return EnumOption
     */
    public function newEnumOption(): EnumOption
    {
        return $this->api->factory(EnumOption::class, $this);
    }

    /**
     * @param bool $global
     * @return $this
     */
    final public function setGlobalToWorkspace(bool $global): static
    {
        return $this->_set('is_global_to_workspace', $global);
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    final public function setNotificationsEnabled(bool $enabled): static
    {
        return $this->_set('has_notifications_enabled', $enabled);
    }

    /**
     * @param callable $cmp `fn( EnumOption $a, EnumOption $b ): int`
     * @return $this
     */
    public function sortEnumOptions(callable $cmp): static
    {
        if ($options = $this->getEnumOptions()) {
            $prev = $options[0]; // first option on remote
            usort($options, $cmp);
            if ($this->hasGid()) {
                foreach ($options as $option) {
                    if ($option !== $prev) {
                        $this->api->put($option, [
                            'insert_after' => $prev->getGid()
                        ]);
                    }
                    $prev = $option;
                }
            }
            $this->data['enum_options'] = $options;
            // no diff
        }
        return $this;
    }

}
