<?php

namespace Helix\Asana\Task;

use Helix\Asana\Base\Data;
use Helix\Asana\Task;

/**
 * Custom task data.
 *
 * @see https://developers.asana.com/docs/custom-external-data
 *
 * @method null|string  getGid  ()
 * @method null|string  getData ()
 *
 * @method bool         hasGid  ()
 * @method bool         hasData ()
 *
 * @method $this        setGid  (?string $gid) 1024 chars max.
 * @method $this        setData (?string $data) 32768 chars max.
 */
class ExternalData extends Data
{

    /**
     * @var Task
     */
    protected $task;

    /**
     * @param Task $task
     * @param array $data
     */
    public function __construct(Task $task, array $data = [])
    {
        $this->task = $task;
        parent::__construct($task, $data);
    }

    /**
     * Marks the task's `external` diff.
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    protected function _set(string $field, $value)
    {
        $this->task->diff['external'] = true;
        return parent::_set($field, $value);
    }

    /**
     * The JSON decoded data, or `null`.
     *
     * @return mixed
     */
    public function getDataJsonDecoded()
    {
        if (strlen($data = $this->getData())) {
            return json_decode($data, true, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
        }
        return null;
    }

    /**
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * JSON encodes and sets.
     * This field is nullable, so `null` is not encoded.
     *
     * @param mixed $data
     * @return $this
     */
    public function setDataJsonEncoded($data)
    {
        if (isset($data)) {
            return $this->setData(json_encode($data, JSON_THROW_ON_ERROR));
        }
        return $this->setData(null);
    }
}