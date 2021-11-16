<?php

namespace Helix\Asana\Base\AbstractEntity;

use Helix\Asana\Base\AbstractEntity;

/**
 * Adds CRUD methods to entities.
 *
 * @mixin AbstractEntity
 *
 * @see CreateTrait::create()
 * @see UpdateTrait::update()
 * @see DeleteTrait::delete()
 */
trait CrudTrait
{

    use CreateTrait;
    use UpdateTrait;
    use DeleteTrait;
}