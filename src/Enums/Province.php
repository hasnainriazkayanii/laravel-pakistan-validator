<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Enums;

/**
 * The first digit of a CNIC identifies the region of registration.
 */
enum Province: int
{
    case KhyberPakhtunkhwa = 1;
    case Fata = 2;
    case Punjab = 3;
    case Sindh = 4;
    case Balochistan = 5;
    case Islamabad = 6;
    case GilgitBaltistan = 7;
    case AzadKashmir = 8;

    public function label(): string
    {
        return match ($this) {
            self::KhyberPakhtunkhwa => 'Khyber Pakhtunkhwa',
            self::Fata => 'FATA',
            self::Punjab => 'Punjab',
            self::Sindh => 'Sindh',
            self::Balochistan => 'Balochistan',
            self::Islamabad => 'Islamabad Capital Territory',
            self::GilgitBaltistan => 'Gilgit-Baltistan',
            self::AzadKashmir => 'Azad Kashmir',
        };
    }
}
