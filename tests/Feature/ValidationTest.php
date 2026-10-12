<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Markhor\PakistanValidator\Casts\CnicCast;
use Markhor\PakistanValidator\Casts\PhoneNumberCast;
use Markhor\PakistanValidator\Rules\PakistaniCnic;
use Markhor\PakistanValidator\Rules\PakistaniPhone;
use Markhor\PakistanValidator\Tests\TestCase;
use Markhor\PakistanValidator\ValueObjects\Cnic;
use Markhor\PakistanValidator\ValueObjects\PhoneNumber;

final class ValidationTest extends TestCase
{
    public function test_string_rules(): void
    {
        $v = Validator::make(
            ['cnic' => '35202-1234567-1', 'mobile' => '0300-1234567', 'land' => '051-1234567'],
            ['cnic' => 'pk_cnic', 'mobile' => 'pk_phone', 'land' => 'pk_phone:landline'],
        );

        $this->assertTrue($v->passes());
    }

    public function test_string_rules_fail_with_translated_messages(): void
    {
        $v = Validator::make(
            ['cnic' => 'abc', 'mobile' => '051-1234567', 'land' => '0300-1234567'],
            ['cnic' => 'pk_cnic', 'mobile' => 'pk_phone', 'land' => 'pk_phone:landline'],
        );

        $this->assertTrue($v->fails());
        $this->assertSame('The cnic must be a valid CNIC (e.g. 35202-1234567-1).', $v->errors()->first('cnic'));
        $this->assertStringContainsString('mobile number', $v->errors()->first('mobile'));
        $this->assertStringContainsString('landline number', $v->errors()->first('land'));
    }

    public function test_custom_messages_are_respected(): void
    {
        $v = Validator::make(['cnic' => 'abc'], ['cnic' => 'pk_cnic'], ['pk_cnic' => 'Bad CNIC!']);

        $this->assertSame('Bad CNIC!', $v->errors()->first('cnic'));
    }

    public function test_rule_objects(): void
    {
        $v = Validator::make(
            ['cnic' => 'nope', 'phone' => '+92 300 1234567'],
            ['cnic' => [new PakistaniCnic], 'phone' => [PakistaniPhone::mobile()]],
        );

        $this->assertSame(['cnic'], $v->errors()->keys());
        $this->assertSame('The cnic must be a valid CNIC (e.g. 35202-1234567-1).', $v->errors()->first('cnic'));
    }

    public function test_urdu_locale(): void
    {
        $this->app->setLocale('ur');

        $v = Validator::make(['cnic' => 'abc'], ['cnic' => [new PakistaniCnic]]);

        $this->assertStringContainsString('شناختی کارڈ', $v->errors()->first('cnic'));
    }

    public function test_unknown_phone_type_parameter_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Validator::make(['p' => '0300-1234567'], ['p' => 'pk_phone:satellite'])->passes();
    }

    public function test_casts_normalize_on_write_and_hydrate_on_read(): void
    {
        $model = new class extends Model {};
        $cnicCast = new CnicCast;
        $phoneCast = new PhoneNumberCast;

        $this->assertSame('3520212345671', $cnicCast->set($model, 'cnic', '35202-1234567-1', []));
        $this->assertInstanceOf(Cnic::class, $cnicCast->get($model, 'cnic', '3520212345671', []));
        $this->assertNull($cnicCast->set($model, 'cnic', '', []));

        $this->assertSame('+923001234567', $phoneCast->set($model, 'phone', '0300-1234567', []));
        $this->assertInstanceOf(PhoneNumber::class, $phoneCast->get($model, 'phone', '+923001234567', []));

        $this->expectException(InvalidArgumentException::class);
        $cnicCast->set($model, 'cnic', 'garbage', []);
    }
}
