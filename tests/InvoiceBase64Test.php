<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class InvoiceBase64Test extends TestCase
{
    public function test_send_invoice_base64(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Send/Base64String' => Http::response([
                'UUID' => 'uuid-1234',
                'InvoiceNumber' => 'GIB2024000000001',
            ], 200),
        ]);

        $result = Nilvera::sendInvoiceBase64('base64zipcontent', 'urn:mail:defaultpk@nilvera.com', 'template-uuid-1');

        $this->assertSame('uuid-1234', $result['UUID']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Send/Base64String'
                && $request->method() === 'POST'
                && $request['ZIPFileBase64'] === 'base64zipcontent'
                && $request['Alias'] === 'urn:mail:defaultpk@nilvera.com'
                && $request['TemplateUUID'] === 'template-uuid-1';
        });
    }

    public function test_send_invoice_base64_without_template(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Send/Base64String' => Http::response([
                'UUID' => 'uuid-5678',
            ], 200),
        ]);

        $result = Nilvera::sendInvoiceBase64('base64zipcontent', 'urn:mail:defaultpk@nilvera.com');

        $this->assertSame('uuid-5678', $result['UUID']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Send/Base64String'
                && $request->method() === 'POST'
                && !array_key_exists('TemplateUUID', $request->data());
        });
    }

    public function test_preview_invoice_base64(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Send/Base64String/Preview' => Http::response('<html>Önizleme</html>', 200),
        ]);

        $result = Nilvera::previewInvoiceBase64('base64zipcontent', 'urn:mail:defaultpk@nilvera.com');

        $this->assertSame('<html>Önizleme</html>', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Send/Base64String/Preview'
                && $request->method() === 'POST'
                && $request['ZIPFileBase64'] === 'base64zipcontent'
                && $request['Alias'] === 'urn:mail:defaultpk@nilvera.com';
        });
    }

    public function test_download_invoice_pdf(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Send/Model/Download/Pdf' => Http::response('%PDF-1.4 binary content', 200),
        ]);

        $result = Nilvera::downloadInvoicePdf(['EInvoice' => ['ID' => 'INV-1'], 'CustomerAlias' => 'urn:mail:defaultpk@nilvera.com']);

        $this->assertSame('%PDF-1.4 binary content', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Send/Model/Download/Pdf'
                && $request->method() === 'POST'
                && $request['CustomerAlias'] === 'urn:mail:defaultpk@nilvera.com';
        });
    }
}
