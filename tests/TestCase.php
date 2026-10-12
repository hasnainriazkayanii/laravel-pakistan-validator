<?php

declare(strict_types=1);

namespace Markhor\PakistanValidator\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Markhor\PakistanValidator\PakistanValidatorServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [PakistanValidatorServiceProvider::class];
    }
}
