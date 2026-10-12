<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Exceptions;

use InvalidArgumentException;

final class InvalidPhoneNumberException extends InvalidArgumentException
{
    public static function make(): self
    {
        return new self('The given value is not a valid Pakistani phone number.');
    }
}
