<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Tests\Unit;

use Markhor\PakistanValidator\Enums\Carrier;
use Markhor\PakistanValidator\Enums\PhoneType;
use Markhor\PakistanValidator\Exceptions\InvalidPhoneNumberException;
use Markhor\PakistanValidator\ValueObjects\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    #[DataProvider('validMobiles')]
    public function test_it_normalizes_mobile_formats(string $input): void
    {
        $phone = PhoneNumber::tryParse($input);

        $this->assertNotNull($phone);
        $this->assertTrue($phone->isMobile());
        $this->assertSame('+923001234567', $phone->e164());
        $this->assertSame('0300-1234567', $phone->national());
        $this->assertSame('+92 300 1234567', $phone->international());
    }

    public static function validMobiles(): array
    {
        return [
            ['03001234567'],
            ['0300-1234567'],
            ['0300 1234567'],
            ['+923001234567'],
            ['+92 300 1234567'],
            ['+92-300-1234567'],
            ['923001234567'],
            ['00923001234567'],
            ['+92(0)3001234567'],
            ['3001234567'],
            ['۰۳۰۰-۱۲۳۴۵۶۷'],
        ];
    }

    #[DataProvider('carriers')]
    public function test_it_detects_carrier_hint(string $input, ?Carrier $expected): void
    {
        $this->assertSame($expected, PhoneNumber::parse($input)->carrier());
    }

    public static function carriers(): array
    {
        return [
            ['0300-1234567', Carrier::Jazz],
            ['0321-1234567', Carrier::Jazz],
            ['0311-1234567', Carrier::Zong],
            ['0333-1234567', Carrier::Ufone],
            ['0345-1234567', Carrier::Telenor],
            ['0355-1234567', Carrier::Scom],
            ['0390-1234567', null],
        ];
    }

    #[DataProvider('landlines')]
    public function test_it_parses_landlines(string $input, string $e164): void
    {
        $phone = PhoneNumber::parse($input);

        $this->assertTrue($phone->isLandline());
        $this->assertNull($phone->carrier());
        $this->assertSame($e164, $phone->e164());
    }

    public static function landlines(): array
    {
        return [
            ['051-1234567', '+92511234567'],
            ['+92 21 12345678', '+922112345678'],
            ['042-35761234', '+924235761234'],
            ['0992-123456', '+92992123456'],
        ];
    }

    #[DataProvider('invalid')]
    public function test_it_rejects_invalid_numbers(mixed $input): void
    {
        $this->assertNull(PhoneNumber::tryParse($input));
    }

    public static function invalid(): array
    {
        return [
            'too short' => ['0300123456'],
            'too long' => ['030012345678'],
            'foreign' => ['+1 300 1234567'],
            'indian' => ['+91 98765 43210'],
            'zero subscriber' => ['0300-0000000'],
            'letters' => ['0300-ABCDEFG'],
            'leading 1' => ['01001234567'],
            'empty' => [''],
            'null' => [null],
            'int' => [3001234567],
        ];
    }

    public function test_type_filtering(): void
    {
        $this->assertTrue(PhoneNumber::isValid('0300-1234567', PhoneType::Mobile));
        $this->assertFalse(PhoneNumber::isValid('0300-1234567', PhoneType::Landline));
        $this->assertTrue(PhoneNumber::isValid('051-1234567', PhoneType::Landline));
        $this->assertFalse(PhoneNumber::isValid('051-1234567', PhoneType::Mobile));
        $this->assertTrue(PhoneNumber::isValid('051-1234567', PhoneType::Any));
    }

    public function test_equality_and_exception(): void
    {
        $this->assertTrue(PhoneNumber::parse('03001234567')->equals('+92 300 1234567'));

        $this->expectException(InvalidPhoneNumberException::class);
        PhoneNumber::parse('nope');
    }
}
