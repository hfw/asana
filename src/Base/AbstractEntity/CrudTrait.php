<?php

namespace Helix\Asana\Base\AbstractEntity;

/**
 * Adds CRUD methods to entities.
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