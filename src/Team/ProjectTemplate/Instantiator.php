<?php

namespace Helix\Asana\Team\ProjectTemplate;

use DateTime;
use DateTimeInterface;
use Helix\Asana\Base\Data;
use Helix\Asana\Job;
use Helix\Asana\Team;
use Helix\Asana\Team\ProjectTemplate;

/**
 * Creates a new {@link Project} from a {@link ProjectTemplate}
 *
 * @see https://developers.asana.com/docs/instantiate-a-project-from-a-project-template
 *
 * @method $this setName        (string $name) required
 * @method $this setPublic      (bool $public) required, defaults to false
 * @method $this setTeam        (Team $team)
 */
class Instantiator extends Data
{

    /**
     * @var ProjectTemplate
     */
    private readonly ProjectTemplate $template;

    /**
     * @param ProjectTemplate $template
     */
    public function __construct(ProjectTemplate $template)
    {
        $this->template = $template;
        parent::__construct($template);
    }

    /***
     * @param string $fakeGid 1|2
     * @param null|string|DateTimeInterface $value
     * @return $this
     */
    protected function _setDate(string $fakeGid, string|DateTimeInterface $value = null): static
    {
        if ($value === null) {
            unset($this->data['requested_dates'], $this->diff['requested_dates']);
            return $this;
        }
        if (is_string($value)) {
            $value = new DateTime($value);
        }
        return $this->_set('requested_dates', [[
            'gid' => $fakeGid,
            'value' => $value->format('Y-m-d')
        ]]);
    }

    /**
     * @return Job Contains the new project when completed.
     */
    public function instantiate(): Job
    {
        return $this->api->factory($this, Job::class,
            $this->api->post("{$this->template}/instantiateProject", $this->toArray())
        );
    }

    /**
     * Cannot be used alongside a start date.
     *
     * @param null|string|DateTimeInterface $due
     * @return $this
     */
    public function setDue(string|DateTimeInterface $due = null): static
    {
        return $this->_setDate('2', $due);
    }

    /**
     * Cannot be used alongside a due date.
     *
     * @param null|string|DateTimeInterface $start
     * @return $this
     */
    public function setStart(string|DateTimeInterface $start = null): static
    {
        return $this->_setDate('1', $start);
    }
}