# Laravel Pakistan Validator

Validation, normalization and value objects for **Pakistani CNIC numbers and phone numbers** in Laravel.

- `pk_cnic` and `pk_phone` validation rules (string and object style)
- Accepts every format users actually type, including Urdu digits, and normalizes to one canonical form
- Immutable `Cnic` and `PhoneNumber` value objects (province, gender, masked CNIC, E.164, carrier hint)
- Eloquent casts that store clean data (`3520212345671`, `+923001234567`)
- English and Urdu error messages
- No config, no migrations, no runtime dependencies beyond Laravel

Requires PHP 8.2+ and Laravel 11 or 12.

## Installation

```bash
composer require markhor/laravel-pakistan-validator
```

The service provider is auto-discovered.

## Validation

```php
use Markhor\PakistanValidator\Rules\PakistaniCnic;
use Markhor\PakistanValidator\Rules\PakistaniPhone;

$request->validate([
    'cnic'      => ['required', 'pk_cnic'],
    'mobile'    => ['required', 'pk_phone'],            // mobile only (default)
    'landline'  => ['nullable', 'pk_phone:landline'],
    'contact'   => ['required', 'pk_phone:any'],

    // or rule objects
    'cnic2'     => [new PakistaniCnic],
    'mobile2'   => [PakistaniPhone::mobile()],
]);
```

| Input | Result |
|---|---|
| `35202-1234567-1`, `3520212345671`, `۳۵۲۰۲-۱۲۳۴۵۶۷-۱` | valid CNIC |
| `0300-1234567`, `03001234567`, `+92 300 1234567`, `923001234567`, `00923001234567`, `3001234567` | valid mobile |
| `051-1234567`, `+92 21 12345678` | valid landline (`pk_phone:landline` / `:any`) |

## Value objects

```php
use Markhor\PakistanValidator\ValueObjects\{Cnic, PhoneNumber};

$cnic = Cnic::parse('35202-1234567-1');   // throws InvalidCnicException when invalid
$cnic->digits;       // 3520212345671
$cnic->formatted();  // 35202-1234567-1
$cnic->masked();     // 35202-*******-1   (safe for logs/UI)
$cnic->province();   // Province::Punjab
$cnic->gender();     // Gender::Male  (odd last digit = male, even = female)

$phone = PhoneNumber::tryParse('0300 1234567');   // null when invalid
$phone->e164();           // +923001234567
$phone->national();       // 0300-1234567
$phone->international();  // +92 300 1234567
$phone->isMobile();       // true
$phone->carrier();        // Carrier::Jazz  (hint only, see below)
```

## Eloquent casts

```php
use Markhor\PakistanValidator\Casts\{CnicCast, PhoneNumberCast};

protected function casts(): array
{
    return [
        'cnic'  => CnicCast::class,         // stored as 13 digits
        'phone' => PhoneNumberCast::class,  // stored as E.164
    ];
}

$user->cnic = '35202-1234567-1';
$user->cnic->masked();
User::where('cnic', Cnic::parse($input)->digits)->first();
```

Assigning an invalid value throws `InvalidArgumentException`, so validate input first.

## Translations

```bash
php artisan vendor:publish --tag=pakistan-validator-lang
```

Messages exist for `en` and `ur`. Custom messages passed to the validator (`'pk_cnic' => '...'`) always win.

## What this package does not do

- **It checks structure, not existence.** There is no public CNIC checksum; confirming that a CNIC or number is real needs NADRA/Verisys or an SMS/OTP check.
- **Carrier detection is a hint.** Mobile Number Portability means a prefix does not prove the current operator.
- **Province and gender are derived from conventions** (first digit, last digit parity) and should not be used for legal decisions.
- CNIC values are personal data. Prefer `masked()` in logs and UI; exceptions never include the offending value.

## Testing

```bash
composer install
composer test
composer analyse
composer format
```

## Contributing

Operator prefix allocations change. PRs updating `Carrier::fromPrefix()` must include a test and a source.

## License

MIT. See [LICENSE.md](LICENSE.md).
