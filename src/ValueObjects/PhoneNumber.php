<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\ValueObjects;

use JsonSerializable;
use Stringable;
use Markhor\PakistanValidator\Enums\Carrier;
use Markhor\PakistanValidator\Enums\PhoneType;
use Markhor\PakistanValidator\Exceptions\InvalidPhoneNumberException;
use Markhor\PakistanValidator\Support\Digits;

/**
 * Immutable, always-valid Pakistani phone number, stored internally as the
 * national significant number (no country code, no trunk "0").
 *
 * Accepts: 03001234567, 0300-1234567, 0300 1234567, +923001234567,
 * +92 300 1234567, 00923001234567, 923001234567, +92(0)3001234567,
 * 3001234567 and landlines such as 051-1234567. Urdu digits are supported.
 */
final class PhoneNumber implements JsonSerializable, Stringable
{
    private function __construct(
        public readonly string $nationalNumber,
        public readonly PhoneType $type,
    ) {}

    public static function parse(mixed $value): self
    {
        return self::tryParse($value) ?? throw InvalidPhoneNumberException::make();
    }

    public static function tryParse(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value instanceof Stringable) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $clean = preg_replace('/[\s\-().]/', '', Digits::toAscii(trim($value)));

        if ($clean === null || $clean === '') {
            return null;
        }

        $national = null;

        if (preg_match('/^(?:\+|00)?92(\d+)$/D', $clean, $m) === 1) {
            $national = (string) preg_replace('/^0/', '', $m[1]); // tolerate +92(0)300...
        } elseif (preg_match('/^0(\d+)$/D', $clean, $m) === 1) {
            $national = $m[1];
        } elseif (preg_match('/^3\d{9}$/D', $clean) === 1) {
            $national = $clean; // bare 3XXXXXXXXX, common when "+92" is a pre-filled prefix
        }

        if ($national === null) {
            return null;
        }

        $type = self::classify($national);

        return $type === null ? null : new self($national, $type);
    }

    public static function isValid(mixed $value, PhoneType $accept = PhoneType::Any): bool
    {
        $phone = self::tryParse($value);

        return $phone !== null && ($accept === PhoneType::Any || $phone->type === $accept);
    }

    private static function classify(string $national): ?PhoneType
    {
        // Mobile: 3XX + 7 subscriber digits (reject 3XX0000000)
        if (preg_match('/^3\d{9}$/D', $national) === 1) {
            return preg_match('/^3\d{2}0{7}$/D', $national) === 1 ? null : PhoneType::Mobile;
        }

        // Landline: area code (2-4 digits) + subscriber number, 9-10 digits total, never starts with 0/1/3
        if (preg_match('/^[24-9]\d{8,9}$/D', $national) === 1) {
            return PhoneType::Landline;
        }

        return null;
    }

    public function isMobile(): bool
    {
        return $this->type === PhoneType::Mobile;
    }

    public function isLandline(): bool
    {
        return $this->type === PhoneType::Landline;
    }

    public function carrier(): ?Carrier
    {
        return $this->isMobile() ? Carrier::fromPrefix(substr($this->nationalNumber, 0, 3)) : null;
    }

    /** +923001234567 — the canonical form to store and send to SMS gateways. */
    public function e164(): string
    {
        return '+92'.$this->nationalNumber;
    }

    /** 0300-1234567 */
    public function national(): string
    {
        return $this->isMobile()
            ? '0'.substr($this->nationalNumber, 0, 3).'-'.substr($this->nationalNumber, 3)
            : '0'.$this->nationalNumber;
    }

    /** +92 300 1234567 */
    public function international(): string
    {
        return $this->isMobile()
            ? '+92 '.substr($this->nationalNumber, 0, 3).' '.substr($this->nationalNumber, 3)
            : '+92 '.$this->nationalNumber;
    }

    public function equals(mixed $other): bool
    {
        $other = self::tryParse($other);

        return $other !== null && $other->nationalNumber === $this->nationalNumber;
    }

    public function __toString(): string
    {
        return $this->e164();
    }

    public function jsonSerialize(): string
    {
        return $this->e164();
    }
}
