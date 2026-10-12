<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Tests\Unit;

use Markhor\PakistanValidator\Enums\Gender;
use Markhor\PakistanValidator\Enums\Province;
use Markhor\PakistanValidator\Exceptions\InvalidCnicException;
use Markhor\PakistanValidator\ValueObjects\Cnic;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CnicTest extends TestCase
{
    #[DataProvider('valid')]
    public function test_it_parses_valid_values(mixed $input): void
    {
        $cnic = Cnic::tryParse($input);

        $this->assertNotNull($cnic);
        $this->assertSame('3520212345671', $cnic->digits);
        $this->assertSame('35202-1234567-1', $cnic->formatted());
    }

    public static function valid(): array
    {
        return [
            'hyphenated' => ['35202-1234567-1'],
            'plain' => ['3520212345671'],
            'padded' => ['  35202-1234567-1 '],
            'urdu digits' => ['۳۵۲۰۲-۱۲۳۴۵۶۷-۱'],
            'int' => [3520212345671],
        ];
    }

    #[DataProvider('invalid')]
    public function test_it_rejects_invalid_values(mixed $input): void
    {
        $this->assertNull(Cnic::tryParse($input));
        $this->assertFalse(Cnic::isValid($input));
    }

    public static function invalid(): array
    {
        return [
            'too short' => ['35202-123456-1'],
            'too long' => ['35202-12345678-1'],
            'partial hyphens' => ['35202-12345671'],
            'letters' => ['35202-123456A-1'],
            'unknown province digit 9' => ['95202-1234567-1'],
            'province digit 0' => ['05202-1234567-1'],
            'repeated digit' => ['11111-1111111-1'],
            'zero family block' => ['35202-0000000-1'],
            'empty' => [''],
            'null' => [null],
            'array' => [['35202-1234567-1']],
        ];
    }

    public function test_it_exposes_metadata(): void
    {
        $male = Cnic::parse('35202-1234567-1');
        $female = Cnic::parse('42101-7654321-2');

        $this->assertSame(Province::Punjab, $male->province());
        $this->assertSame(Gender::Male, $male->gender());
        $this->assertSame('35202', $male->localityCode());
        $this->assertSame('35202-*******-1', $male->masked());
        $this->assertSame(Province::Sindh, $female->province());
        $this->assertSame(Gender::Female, $female->gender());
    }

    public function test_equality_ignores_formatting(): void
    {
        $this->assertTrue(Cnic::parse('35202-1234567-1')->equals('3520212345671'));
        $this->assertFalse(Cnic::parse('35202-1234567-1')->equals('3520212345672'));
        $this->assertFalse(Cnic::parse('35202-1234567-1')->equals('nope'));
    }

    public function test_parse_throws_without_leaking_the_value(): void
    {
        try {
            Cnic::parse('35202-1234567');
            $this->fail('Expected exception');
        } catch (InvalidCnicException $e) {
            $this->assertStringNotContainsString('35202', $e->getMessage());
        }
    }

    public function test_string_and_json_representations(): void
    {
        $cnic = Cnic::parse('3520212345671');

        $this->assertSame('35202-1234567-1', (string) $cnic);
        $this->assertSame('"35202-1234567-1"', json_encode($cnic));
    }
}
