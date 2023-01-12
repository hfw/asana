<?php

namespace Helix\Asana\Base\AbstractEntity;

/**
 * Marker interface for entities that cannot be altered by the API after creation.
 *
 * Some entities can be deleted, others cannot.
 *
 * @immutable Marker interface.
 */
interface ImmutableInterface
{

}