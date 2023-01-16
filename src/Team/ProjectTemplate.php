<?php

namespace Helix\Asana\Team;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Team;
use Helix\Asana\Team\ProjectTemplate\Instantiator;
use Helix\Asana\User;

/**
 * A team project template.
 *
 * @immutable Project templates cannot be altered via the API.
 *
 * @see https://developers.asana.com/docs/project-templates
 *
 * @method string   getName     ()
 * @method User     getOwner    ()
 * @method Team     getTeam     ()
 */
class ProjectTemplate extends AbstractEntity
{

    final protected const DIR = 'project_templates';
    final public const TYPE = 'project_template';

    protected const MAP = [
        'owner' => User::class,
        'team' => Team::class,
    ];

    /**
     * @return Instantiator
     */
    public function getInstantiator(): Instantiator
    {
        return $this->api->factory(Instantiator::class, $this)
            ->setPublic(false)
            ->setTeam($this->getTeam());
    }

}