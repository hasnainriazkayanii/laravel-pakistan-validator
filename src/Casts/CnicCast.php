<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Markhor\PakistanValidator\ValueObjects\Cnic;

/**
 * Stores the 13 plain digits (index/unique friendly), hydrates a Cnic object.
 *
 * @implements CastsAttributes<Cnic|null, Cnic|string|int|null>
 */
final class CnicCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Cnic
    {
        return $value === null || $value === '' ? null : Cnic::tryParse($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null || $value === '' ? null : Cnic::parse($value)->digits;
    }
}
