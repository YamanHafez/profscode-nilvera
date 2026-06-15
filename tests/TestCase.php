<?php

namespace ProfsCode\Nilvera\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use ProfsCode\Nilvera\NilveraServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            NilveraServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('nilvera.base_url', 'https://apitest.nilvera.com');
        $app['config']->set('nilvera.api_key', 'test-api-key');
    }
}
