<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\ValueObjects;

use JsonSerializable;
use Stringable;
use Markhor\PakistanValidator\Enums\Gender;
use Markhor\PakistanValidator\Enums\Province;
use Markhor\PakistanValidator\Exceptions\InvalidCnicException;
use Markhor\PakistanValidator\Support\Digits;

/**
 * Immutable, always-valid Pakistani CNIC (13 digits: XXXXX-XXXXXXX-X).
 *
 * Validates structure only. There is no public checksum, so existence
 * can only be confirmed by NADRA/Verisys.
 */
final class Cnic implements JsonSerializable, Stringable
{
    private function __construct(public readonly string $digits) {}

    public static function parse(mixed $value): self
    {
        return self::tryParse($value) ?? throw InvalidCnicException::make();
    }

    public static function tryParse(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_int($value) || $value instanceof Stringable) {
            $value = (string) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        $value = Digits::toAscii(trim($value));

        if (preg_match('/^\d{13}$/D', $value) === 1) {
            $digits = $value;
        } elseif (preg_match('/^(\d{5})-(\d{7})-(\d)$/D', $value, $m) === 1) {
            $digits = $m[1].$m[2].$m[3];
        } else {
            return null;
        }

        if (Province::tryFrom((int) $digits[0]) === null) {
            return null;
        }

        // Reject obvious placeholders: 1111111111111, 35202-0000000-1
        if (preg_match('/^(\d)\1{12}$/D', $digits) === 1 || substr($digits, 5, 7) === '0000000') {
            return null;
        }

        return new self($digits);
    }

    public static function isValid(mixed $value): bool
    {
        return self::tryParse($value) !== null;
    }

    /** 35202-1234567-1 */
    public function formatted(): string
    {
        return substr($this->digits, 0, 5).'-'.substr($this->digits, 5, 7).'-'.substr($this->digits, 12, 1);
    }

    /** 35202-*******-1, safe for logs and UI. */
    public function masked(): string
    {
        return substr($this->digits, 0, 5).'-*******-'.substr($this->digits, 12, 1);
    }

    public function localityCode(): string
    {
        return substr($this->digits, 0, 5);
    }

    public function province(): Province
    {
        return Province::from((int) $this->digits[0]);
    }

    /** Last digit: odd = male, even = female. */
    public function gender(): Gender
    {
        return ((int) $this->digits[12]) % 2 === 1 ? Gender::Male : Gender::Female;
    }

    public function equals(mixed $other): bool
    {
        $other = self::tryParse($other);

        return $other !== null && $other->digits === $this->digits;
    }

    public function __toString(): string
    {
        return $this->formatted();
    }

    public function jsonSerialize(): string
    {
        return $this->formatted();
    }
}
