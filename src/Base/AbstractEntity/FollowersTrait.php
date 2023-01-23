<?php

namespace Helix\Asana\Base\AbstractEntity;

use Helix\Asana\User;

/**
 * The resource has followers.
 *
 * @method User[]   getFollowers    ()
 * @method bool     hasFollowers    ()
 * @method User[]   selectFollowers (callable $filter) `fn( User $user ): bool`
 */
trait FollowersTrait
{

    use PostMutatorTrait;

    /**
     * @param User $user
     * @return $this
     */
    public function addFollower(User $user): static
    {
        return $this->addFollowers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function addFollowers(array $users): static
    {
        return $this->_addWithPost("{$this}/addFollowers", [
            'followers' => array_column($users, 'gid')
        ], 'followers', $users);
    }

    /**
     * @param User $user
     * @return $this
     */
    public function removeFollower(User $user): static
    {
        return $this->removeFollowers([$user]);
    }

    /**
     * @param User[] $users
     * @return $this
     */
    public function removeFollowers(array $users): static
    {
        return $this->_removeWithPost("{$this}/removeFollowers", [
            'followers' => array_column($users, 'gid')
        ], 'followers', $users);
    }

}
