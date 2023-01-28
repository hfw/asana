<?php

namespace Helix\Asana\Task\FieldEntry;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Helix\Asana\Api;
use Helix\Asana\Base\Data;

/**
 * A custom-field's selected date-time.
 *
 * @method string       getDate     () YYYY-MM-DD
 * @method null|string  getDateTime () ISO-8601, or `null` when there is no time selected
 * @method bool         hasDateTime ()
 */
class Date extends Data
{

    /**
     * @param Data|Api $caller
     * @param string|DateTimeInterface|array $spec Date-like, or data from Asana.
     */
    public function __construct(Data|Api $caller, $spec)
    {
        if (is_string($spec)) {
            $spec = new DateTime($spec);
        }
        if ($spec instanceof DateTimeInterface) {
            if ($spec->format('His') === '000000'
                and $tz = $spec->getTimezone()
                and $tz->getName() === date_default_timezone_get()
            ) { // midnight in the runtime's tz
                $spec = ['date' => $spec->format('Y-m-d')];
            } else {
                $spec = ['date_time' => $spec->format(DateTimeInterface::ATOM)];
            }
        }
        parent::__construct($caller, $spec);
    }

    /**
     * ISO-8601
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->asDT()->format(DateTimeInterface::ATOM);
    }

    /**
     * A date-time object, either in the true timezone if a time was selected,
     * or the runtime's timezone if only a date was selected.
     *
     * @return DateTimeImmutable
     */
    public function asDT(): DateTimeInterface
    {
        return $this->api->factory(DateTimeImmutable::class, $this->getDateTime() ?? $this->getDate());
    }

}
