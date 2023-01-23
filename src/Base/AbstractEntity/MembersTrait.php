<?php

namespace Helix\Asana\Base\AbstractEntity;

use Helix\Asana\User;

/**
 * The resource has members.
 *
 * @method User[]   getMembers      ()
 * @method bool     hasMembers      ()
 * @method User[]   selectMembers   (callable $filter) `fn( User $user ): bool`
 */
trait MembersTrait
{

    use PostMutatorTrait;

    /**
     * @param User $user
     * @return $this
     */
    public function addMember(User $user): static
    {
        return $this->addMembers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function addMembers(array $users): static
    {
        return $this->_addWithPost("{$this}/addMembers", [
            'members' => array_column($users, 'gid')
        ], 'members', $users);
    }

    /**
     * @param User $user
     * @return $this
     */
    public function removeMember(User $user): static
    {
        return $this->removeMembers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function removeMembers(array $users): static
    {
        return $this->_removeWithPost("{$this}/removeMembers", [
            'members' => array_column($users, 'gid')
        ], 'members', $users);
    }

}
