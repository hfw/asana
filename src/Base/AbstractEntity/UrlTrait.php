<?php

namespace Helix\Asana\Base\AbstractEntity;

trait UrlTrait
{
    /**
     * @return string
     */
    final public function getUrl(): string
    {
        return $this->_get('permalink_url');
    }
}