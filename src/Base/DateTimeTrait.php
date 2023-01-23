<?php

namespace Helix\Asana\Base;

use DateTimeImmutable;
use DateTimeInterface;

trait DateTimeTrait
{
    /**
     * Returns a date-time field as an immutable object.
     *
     * Imported as `<getActualField>DT()`
     *
     * @return null|DateTimeInterface
     */
    public function _getDateTime(): ?DateTimeInterface
    {
        $alias = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['function'];
        $getter = substr($alias, 0, -2);
        $spec = $this->{$getter}();
        return isset($spec) ? $this->api->factory(DateTimeImmutable::class, $spec) : null;
    }
}