<?php

namespace Helix\Asana\Api;

use Helix\Asana\Api;
use RuntimeException;

/**
 * An {@link Api} error.
 *
 * Codes less than `400` are cURL errors.
 *
 * > :info:
 * > The {@link Api} returns `null` on `404`, it doesn't throw.
 *
 * @see https://developers.asana.com/docs/errors
 */
class AsanaError extends RuntimeException
{

    /**
     * cURL context
     *
     * @var array
     */
    public readonly array $curlInfo;

    /**
     * @param int $code
     * @param string $message
     * @param array $curlInfo
     */
    public function __construct(int $code, string $message, array $curlInfo)
    {
        parent::__construct($message, $code);
        $this->curlInfo = $curlInfo;
    }

    /**
     * Decodes and returns Asana's error JSON.
     *
     * @return array
     */
    final public function asResponse(): array
    {
        return json_decode($this->getMessage(), true, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
    }

    /**
     * @param int $code
     * @return bool
     */
    final public function is(int $code): bool
    {
        return $this->code === $code;
    }

    /**
     * Whether the error was due to cURL failure.
     *
     * @return bool
     */
    final public function isCurl(): bool
    {
        return $this->code < 400;
    }
}