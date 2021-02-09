<?php

namespace Helix\Asana\Base\AbstractEntity;

/**
 * Marker interface for entities that cannot be altered after creation.
 *
 * Certain entities may be deleted. Others cannot.
 *
 * Cache TTL is indefinite.
 *
 * @immutable Marker interface.
 */
interface ImmutableInterface {

}