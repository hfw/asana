<?php

namespace Helix\Asana\Base;

use DateTimeImmutable;

trait DateTimeTrait
{
    /**
     * Returns a date-time field as an object.
     *
     * Imported as `<getActualField>DT()`
     *
     * @return null|DateTimeImmutable
     */
    public function _getDateTime(): ?DateTimeImmutable
    {
        $alias = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['function'];
        $getter = substr($alias, 0, -2);
        $spec = $this->{$getter}();
        return isset($spec) ? $this->api->factory(DateTimeImmutable::class, $spec) : null;
    }
}