<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Enums;

/**
 * Original allocation of the mobile prefix. Because of Mobile Number
 * Portability this is a hint, never proof of the current operator.
 */
enum Carrier: string
{
    case Jazz = 'jazz';
    case Zong = 'zong';
    case Ufone = 'ufone';
    case Telenor = 'telenor';
    case Scom = 'scom';

    /** @param string $prefix First three digits of the national mobile number, e.g. "300". */
    public static function fromPrefix(string $prefix): ?self
    {
        $p = (int) $prefix;

        return match (true) {
            ($p >= 300 && $p <= 309) || ($p >= 320 && $p <= 329) => self::Jazz,
            $p >= 310 && $p <= 319 => self::Zong,
            $p >= 330 && $p <= 337 => self::Ufone,
            $p >= 340 && $p <= 349 => self::Telenor,
            $p === 355 => self::Scom,
            default => null,
        };
    }
}
