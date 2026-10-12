<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Enums;

enum PhoneType: string
{
    case Mobile = 'mobile';
    case Landline = 'landline';

    /** Only meaningful as a validation requirement ("accept either"). */
    case Any = 'any';
}
