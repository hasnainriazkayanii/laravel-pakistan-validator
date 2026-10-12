<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Exceptions;

use InvalidArgumentException;

final class InvalidCnicException extends InvalidArgumentException
{
    /** The offending value is deliberately NOT included: a CNIC is personal data and must not leak into logs. */
    public static function make(): self
    {
        return new self('The given value is not a valid Pakistani CNIC.');
    }
}
