<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Markhor\PakistanValidator\ValueObjects\Cnic;

final class PakistaniCnic implements ValidationRule
{
    public function passes(mixed $value): bool
    {
        return Cnic::isValid($value);
    }

    public function messageKey(): string
    {
        return 'pakistan-validator::validation.cnic';
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->passes($value)) {
            $fail($this->messageKey())->translate();
        }
    }
}
