<?php

namespace Helix\Asana\Project;

use Helix\Asana\Base\Data;

/**
 * Project task count summary.
 *
 * @see https://developers.asana.com/docs/get-task-count-of-a-project
 *
 * @method int  getNumCompletedMilestones   ()
 * @method int  getNumCompletedTasks        ()
 * @method int  getNumIncompleteMilestones  ()
 * @method int  getNumIncompleteTasks       ()
 * @method int  getNumMilestones            ()
 * @method int  getNumTasks                 ()
 */
class TaskCounts extends Data
{

}