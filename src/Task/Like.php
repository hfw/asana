<?php

namespace Helix\Asana\Task;

use Helix\Asana\Base\Data;
use Helix\Asana\User;

/**
 * A "like".
 *
 * @method User getUser ()
 */
class Like extends Data
{

    const TYPE = 'like';

    protected const MAP = [
        'user' => User::class
    ];

    protected function _setData(array $data): void
    {
        // useless. likes aren't entities.
        unset($data['gid'], $data['resource_type']);

        parent::_setData($data);
    }

}