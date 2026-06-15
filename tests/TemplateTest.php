<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class TemplateTest extends TestCase
{
    public function test_update_template(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Templates' => Http::response('true', 200),
        ]);

        $result = Nilvera::updateTemplate(1, name: 'Yeni Şablon', isActive: true, isDefault: true);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Templates'
                && $request->method() === 'PUT'
                && $request['ID'] === 1
                && $request['Name'] === 'Yeni Şablon'
                && $request['IsActive'] === true
                && $request['IsDefault'] === true;
        });
    }

    public function test_get_template_detail(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Templates/1' => Http::response([
                'ID' => 1,
                'Name' => 'Standart Şablon',
                'IsActive' => true,
                'IsDefault' => true,
            ], 200),
        ]);

        $result = Nilvera::getTemplateDetail(1);

        $this->assertSame('Standart Şablon', $result['Name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Templates/1'
                && $request->method() === 'GET';
        });
    }

    public function test_delete_template(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Templates/1' => Http::response('true', 200),
        ]);

        $result = Nilvera::deleteTemplate(1);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Templates/1'
                && $request->method() === 'DELETE';
        });
    }

    public function test_preview_template(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Templates/Preview/uuid-1234' => Http::response('<html>Önizleme</html>', 200),
        ]);

        $result = Nilvera::previewTemplate('uuid-1234');

        $this->assertSame('<html>Önizleme</html>', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Templates/Preview/uuid-1234'
                && $request->method() === 'GET';
        });
    }

    public function test_update_template_for_earchive(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Templates' => Http::response('true', 200),
        ]);

        $result = Nilvera::updateTemplate(2, isActive: false, mode: 'earchive');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Templates'
                && $request->method() === 'PUT'
                && $request['ID'] === 2
                && $request['IsActive'] === false
                && !array_key_exists('Name', $request->data())
                && !array_key_exists('IsDefault', $request->data());
        });
    }
}
