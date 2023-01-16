<?php

namespace Helix\Asana\Base;

use DateTimeImmutable;
use DateTimeInterface;

trait DateTimeTrait
{
    /**
     * Returns a date-time field as an object.
     *
     * Imported as `<getActualField>DT()`
     *
     * @template T of DateTimeInterface
     * @param class-string<T> $class
     * @return null|T
     */
    public function _getDateTime($class = DateTimeImmutable::class): ?DateTimeInterface
    {
        $alias = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['function'];
        $getter = substr($alias, 0, -2);
        $spec = $this->{$getter}();
        return isset($spec) ? new $class($spec) : null;
    }
}