<?php

namespace Helix\Asana\Base;

use DomainException;

/**
 * Mutation via array-access is not allowed by default.
 */
trait ImmutableArrayTrait
{
    /**
     * Throws unless overridden.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     * @throws DomainException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        unset($offset, $value);
        throw new DomainException("Mutation via ArrayAccess is not allowed for " . static::class);
    }
}
