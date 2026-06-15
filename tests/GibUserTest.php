<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class GibUserTest extends TestCase
{
    public function test_get_gib_user_credentials(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/GibEArchiveAccount' => Http::response([
                'UserName' => 'gibuser',
                'Password' => 'gibpass',
            ], 200),
        ]);

        $result = Nilvera::getGibUserCredentials();

        $this->assertSame('gibuser', $result['UserName']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/GibEArchiveAccount'
                && $request->method() === 'GET';
        });
    }

    public function test_update_gib_user_credentials(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/GibEArchiveAccount*' => Http::response('true', 200),
        ]);

        $result = Nilvera::updateGibUserCredentials('gibuser', 'gibpass');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://apitest.nilvera.com/General/GibEArchiveAccount?')
                && $request->method() === 'PUT'
                && str_contains($request->url(), 'UserName=gibuser')
                && str_contains($request->url(), 'Password=gibpass');
        });
    }
}
