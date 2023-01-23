<?php

namespace Helix\Asana\Base\AbstractEntity;

use Helix\Asana\Task\Like;

/**
 * The resource can be "liked".
 *
 * @method Like[]   getLikes    ()
 * @method int      getNumLikes ()
 * @method bool     hasLikes    ()
 * @method bool     isLiked     () Whether you like this.
 * @method Like[]   selectLikes (callable $filter) `fn( Like $like ): bool`
 * @method $this    setLiked    (bool $liked) Sets whether you like this.
 */
trait LikesTrait
{

}
