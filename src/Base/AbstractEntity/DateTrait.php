<?php

namespace Helix\Asana\Base\AbstractEntity;

use DateTimeInterface;
use Helix\Asana\Base\AbstractEntity;

/**
 * Adds date helpers.
 *
 * @mixin AbstractEntity
 *
 * @method null|string  getDueOn    () `Y-m-d`
 * @method bool         hasDueOn    ()
 * @method null|string  getStartOn  () `Y-m-d`
 * @method bool         hasStartOn  ()
 */
trait DateTrait
{

    /**
     * @param string $field
     * @param null|string|DateTimeInterface $date
     * @return $this
     */
    private function _setYmd(string $field, $date)
    {
        if ($date instanceof DateTimeInterface) {
            $date = $date->format('Y-m-d');
        }
        return $this->_set($field, $date);
    }

    /**
     * @param null|string|DateTimeInterface $date
     * @return $this
     */
    public function setDueOn($date)
    {
        return $this->_setYmd('due_on', $date);
    }

    /**
     * @param null|string|DateTimeInterface $date
     * @return $this
     */
    public function setStartOn($date)
    {
        // Asana says the due date must be present in the request when changing the start date.
        $this->setDueOn($this->getDueOn());
        return $this->_setYmd('start_on', $date);
    }

}