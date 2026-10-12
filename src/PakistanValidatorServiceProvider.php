<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator;

use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Validator as ValidatorInstance;
use Markhor\PakistanValidator\Rules\PakistaniCnic;
use Markhor\PakistanValidator\Rules\PakistaniPhone;

final class PakistanValidatorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'pakistan-validator');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/lang' => $this->app->langPath('vendor/pakistan-validator'),
            ], 'pakistan-validator-lang');
        }

        // 'pk_cnic'
        $this->extendRule('pk_cnic', static fn (array $params): PakistaniCnic => new PakistaniCnic);

        // 'pk_phone', 'pk_phone:mobile', 'pk_phone:landline', 'pk_phone:any'
        $this->extendRule('pk_phone', static fn (array $params): PakistaniPhone => PakistaniPhone::fromParameter($params[0] ?? null));
    }

    /**
     * @param  Closure(array<int, string>): (PakistaniCnic|PakistaniPhone)  $make
     */
    private function extendRule(string $name, Closure $make): void
    {
        Validator::extend($name, static function (string $attribute, mixed $value, array $parameters, ValidatorInstance $validator) use ($name, $make): bool {
            $rule = $make($parameters);

            if ($rule->passes($value)) {
                return true;
            }

            // Lazily resolve the message in the *current* locale, without clobbering
            // messages the developer set explicitly (generic or per-attribute).
            if (! isset($validator->customMessages[$name])) {
                $validator->customMessages["{$attribute}.{$name}"] ??= (string) trans($rule->messageKey());
            }

            return false;
        });
    }
}
