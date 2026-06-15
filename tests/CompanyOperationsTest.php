<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class CompanyOperationsTest extends TestCase
{
    public function test_get_company_info(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Company' => Http::response([
                'Name' => 'Test Kurum Bir',
                'TaxNumber' => '1234567801',
            ], 200),
        ]);

        $result = Nilvera::getCompanyInfo();

        $this->assertSame('Test Kurum Bir', $result['Name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Company'
                && $request->method() === 'GET';
        });
    }

    public function test_update_company_info(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Company' => Http::response([
                'Name' => 'Test Kurum Bir',
                'Email' => 'info@example.com',
            ], 200),
        ]);

        $result = Nilvera::updateCompanyInfo(['Email' => 'info@example.com']);

        $this->assertSame('info@example.com', $result['Email']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Company'
                && $request->method() === 'PUT'
                && $request['Email'] === 'info@example.com';
        });
    }

    public function test_get_company_modules(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Company/Modules' => Http::response([
                ['Name' => 'EInvoice', 'IsActive' => true, 'IsActivation' => true],
            ], 200),
        ]);

        $result = Nilvera::getCompanyModules();

        $this->assertSame('EInvoice', $result[0]['Name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Company/Modules'
                && $request->method() === 'GET';
        });
    }

    public function test_get_company_certificates(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Company/Certificate' => Http::response([
                ['ID' => 1, 'SerialNo' => 'ABC123', 'Type' => 'EIMZA'],
            ], 200),
        ]);

        $result = Nilvera::getCompanyCertificates();

        $this->assertSame('ABC123', $result[0]['SerialNo']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Company/Certificate'
                && $request->method() === 'GET';
        });
    }

    public function test_add_company_certificate(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Company/Certificate' => Http::response('true', 200),
        ]);

        $result = Nilvera::addCompanyCertificate([
            'SerialNo' => 'ABC123',
            'Type' => 'EIMZA',
            'Password' => 'secret',
        ]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Company/Certificate'
                && $request->method() === 'POST'
                && $request['SerialNo'] === 'ABC123';
        });
    }

    public function test_update_company_certificate(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Company/Certificate' => Http::response('true', 200),
        ]);

        $result = Nilvera::updateCompanyCertificate([
            'ID' => 1,
            'Type' => 'EIMZA',
            'SerialNo' => 'ABC123',
        ]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Company/Certificate'
                && $request->method() === 'PUT'
                && $request['ID'] === 1;
        });
    }

    public function test_get_company_certificate_by_serial(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Company/Certificate/ABC123' => Http::response([
                'ID' => 1,
                'SerialNo' => 'ABC123',
                'Type' => 'EIMZA',
            ], 200),
        ]);

        $result = Nilvera::getCompanyCertificateBySerial('ABC123');

        $this->assertSame('ABC123', $result['SerialNo']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Company/Certificate/ABC123'
                && $request->method() === 'GET';
        });
    }

    public function test_delete_company_certificate(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Company/Certificate/1' => Http::response('true', 200),
        ]);

        $result = Nilvera::deleteCompanyCertificate(1);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Company/Certificate/1'
                && $request->method() === 'DELETE';
        });
    }

    public function test_get_user_companies(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Company/List' => Http::response([
                ['Name' => 'Test Kurum Bir', 'TaxNumber' => '1234567801'],
            ], 200),
        ]);

        $result = Nilvera::getUserCompanies();

        $this->assertSame('Test Kurum Bir', $result[0]['Name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Company/List'
                && $request->method() === 'GET';
        });
    }

    public function test_debug_mode_returns_curl_command(): void
    {
        $curl = Nilvera::debug()->getCompanyInfo();

        $this->assertStringContainsString('curl -X GET', $curl);
        $this->assertStringContainsString('General/Company', $curl);
        $this->assertStringContainsString('Authorization: Bearer test-api-key', $curl);
    }
}
