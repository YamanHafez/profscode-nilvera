<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class CompanyIdentityTest extends TestCase
{
    public function test_get_company_identities(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/CompanyIdentification' => Http::response([
                ['ID' => 1, 'CompanyID' => 1, 'Name' => 'MERSISNO', 'Value' => '0123456789012345'],
            ], 200),
        ]);

        $result = Nilvera::getCompanyIdentities();

        $this->assertSame('MERSISNO', $result[0]['Name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/CompanyIdentification'
                && $request->method() === 'GET';
        });
    }

    public function test_add_company_identity(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/CompanyIdentification*' => Http::response([
                'ID' => 1,
                'CompanyID' => 1,
                'Name' => 'MERSISNO',
                'Value' => '0123456789012345',
            ], 200),
        ]);

        $result = Nilvera::addCompanyIdentity('MERSISNO', '0123456789012345');

        $this->assertSame('MERSISNO', $result['Name']);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://apitest.nilvera.com/General/CompanyIdentification?')
                && $request->method() === 'POST'
                && $request->data() === []
                && str_contains($request->url(), 'Name=MERSISNO')
                && str_contains($request->url(), 'Value=0123456789012345');
        });
    }

    public function test_delete_company_identity(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/CompanyIdentification/1' => Http::response('true', 200),
        ]);

        $result = Nilvera::deleteCompanyIdentity(1);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/CompanyIdentification/1'
                && $request->method() === 'DELETE';
        });
    }
}
