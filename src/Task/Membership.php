<?php

namespace Helix\Asana\Task;

use Helix\Asana\Base\Data;
use Helix\Asana\Project;
use Helix\Asana\Project\Section;
use Helix\Asana\Task;

/**
 * A task's membership.
 *
 * @see https://developers.asana.com/docs/task
 *
 * @method Project getProject ()
 * @method Section getSection ()
 */
class Membership extends Data
{

    protected const MAP = [
        'project' => Project::class,
        'section' => Section::class
    ];

    /**
     * @depends create-only
     *
     * @see     Task::addToProject()
     *
     * @param Section $section
     * @return $this
     */
    final public function setSection(Section $section)
    {
        $this->_set('project', $section->getProject());
        $this->_set('section', $section);
        return $this;
    }
}