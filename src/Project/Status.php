<?php

namespace Helix\Asana\Project;

use Helix\Asana\Base\AbstractEntity;
use Helix\Asana\Base\AbstractEntity\CreateTrait;
use Helix\Asana\Base\AbstractEntity\DeleteTrait;
use Helix\Asana\Base\AbstractEntity\ImmutableInterface;
use Helix\Asana\Project;
use Helix\Asana\User;

/**
 * A project status.
 *
 * @immutable Statuses can only be created and deleted.
 *
 * @see https://developers.asana.com/docs/asana-project-statuses
 * @see https://developers.asana.com/docs/project-status
 *
 * @see Project::newStatus()
 *
 * @method $this    setColor        (string $color)     @depends create-only, `green|red|yellow`
 * @method $this    setText         (string $text)      @depends create-only
 * @method $this    setTitle        (string $title)     @depends create-only
 *
 * @method string   getColor        () `green|red|yellow`
 * @method string   getCreatedAt    () RFC3339x
 * @method User     getCreatedBy    ()
 * @method string   getText         ()
 * @method string   getTitle        ()
 */
class Status extends AbstractEntity implements ImmutableInterface
{

    use CreateTrait {
        create as private _create;
    }
    use DeleteTrait {
        delete as private _delete;
    }

    final protected const DIR = 'project_statuses';
    final public const TYPE = 'project_status';

    final public const COLOR_GREEN = 'green';
    final public const COLOR_RED = 'red';
    final public const COLOR_YELLOW = 'yellow';

    protected const MAP = [
        'created_by' => User::class
    ];

    /**
     * @var Project
     */
    private readonly Project $project;

    /**
     * @param Project $project
     * @param array $data
     */
    public function __construct(Project $project, array $data = [])
    {
        $this->project = $project;
        parent::__construct($project, $data);
    }

    /**
     * @return Project
     */
    final protected function _getParentNode(): Project
    {
        return $this->project;
    }

    /**
     * @param array $data
     * @return void
     */
    protected function _setData(array $data): void
    {
        // redundant, prefer created_by
        unset($data['author']);

        // statuses are immutable and asana doesn't accept or return this field despite being documented.
        unset($data['modified_at']);

        parent::_setData($data);
    }

    /**
     * @return $this
     */
    public function create(): static
    {
        $this->_create();
        $this->project->_reload('current_status');
        return $this;
    }

    /**
     * @return void
     */
    public function delete(): void
    {
        $this->_delete();
        $this->project->_reload('current_status');
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }
}