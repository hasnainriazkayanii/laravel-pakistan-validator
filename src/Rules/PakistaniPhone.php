<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;
use Markhor\PakistanValidator\Enums\PhoneType;
use Markhor\PakistanValidator\ValueObjects\PhoneNumber;

final class PakistaniPhone implements ValidationRule
{
    public function __construct(private readonly PhoneType $accept = PhoneType::Mobile) {}

    public static function mobile(): self
    {
        return new self(PhoneType::Mobile);
    }

    public static function landline(): self
    {
        return new self(PhoneType::Landline);
    }

    public static function any(): self
    {
        return new self(PhoneType::Any);
    }

    /** Builds the rule from the string-rule parameter: pk_phone, pk_phone:landline, pk_phone:any */
    public static function fromParameter(?string $parameter): self
    {
        $type = PhoneType::tryFrom($parameter ?? PhoneType::Mobile->value)
            ?? throw new InvalidArgumentException("Unknown pk_phone type [{$parameter}]. Use mobile, landline or any.");

        return new self($type);
    }

    public function passes(mixed $value): bool
    {
        return PhoneNumber::isValid($value, $this->accept);
    }

    public function messageKey(): string
    {
        return 'pakistan-validator::validation.phone_'.$this->accept->value;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->passes($value)) {
            $fail($this->messageKey())->translate();
        }
    }
}
