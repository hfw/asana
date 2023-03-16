<?php

namespace Helix\Asana\Base;

use Psr\Log\LoggerInterface;

/**
 * Adds logging to core components.
 */
trait LogTrait
{
    /**
     * @var null|LoggerInterface
     */
    protected ?LoggerInterface $log;

    /**
     * @return null|LoggerInterface
     */
    public function getLog(): ?LoggerInterface
    {
        return $this->log;
    }

    /**
     * @param null|LoggerInterface $log
     * @return $this
     */
    final public function setLog(?LoggerInterface $log): static
    {
        $this->log = $log;
        return $this;
    }
}
