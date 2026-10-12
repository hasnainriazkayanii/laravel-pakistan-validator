<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Markhor\PakistanValidator\ValueObjects\PhoneNumber;

/**
 * Stores E.164 (+923001234567), hydrates a PhoneNumber object.
 *
 * @implements CastsAttributes<PhoneNumber|null, PhoneNumber|string|null>
 */
final class PhoneNumberCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PhoneNumber
    {
        return $value === null || $value === '' ? null : PhoneNumber::tryParse($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null || $value === '' ? null : PhoneNumber::parse($value)->e164();
    }
}
