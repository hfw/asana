<?php

namespace Helix\Asana\Base\AbstractEntity;

use Helix\Asana\User;

/**
 * The resource has users.
 *
 * @method User[] selectUsers (callable $filter) `fn( User $user ): bool`
 */
trait UsersTrait
{

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        return $this->api->loadAll($this, User::class, "{$this}/users");
    }

}
