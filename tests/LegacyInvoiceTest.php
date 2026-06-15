<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class LegacyInvoiceTest extends TestCase
{
    public function test_get_old_invoice_html(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Old/uuid-1234/html' => Http::response('<html>eski fatura</html>', 200),
        ]);

        $result = Nilvera::getOldInvoiceHtml('uuid-1234');

        $this->assertSame('<html>eski fatura</html>', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Old/uuid-1234/html'
                && $request->method() === 'GET';
        });
    }

    public function test_get_old_invoice_pdf(): void
    {
        $pdfContent = 'PDF-CONTENT';

        Http::fake([
            'apitest.nilvera.com/EInvoice/Old/uuid-1234/pdf' => Http::response('"' . base64_encode($pdfContent) . '"', 200),
        ]);

        $result = Nilvera::getOldInvoicePdf('uuid-1234');

        $this->assertSame($pdfContent, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Old/uuid-1234/pdf'
                && $request->method() === 'GET';
        });
    }

    public function test_get_old_invoice_xml(): void
    {
        $xmlContent = '<Invoice>eski</Invoice>';

        Http::fake([
            'apitest.nilvera.com/EArchive/Old/uuid-1234/xml' => Http::response('"' . base64_encode($xmlContent) . '"', 200),
        ]);

        $result = Nilvera::getOldInvoiceXml('uuid-1234', 'earchive');

        $this->assertSame($xmlContent, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Old/uuid-1234/xml'
                && $request->method() === 'GET';
        });
    }

    public function test_export_old_invoices(): void
    {
        $fileContent = 'EXPORTED-CONTENT';

        Http::fake([
            'apitest.nilvera.com/EInvoice/Old/Export/Pdf' => Http::response('"' . base64_encode($fileContent) . '"', 200),
        ]);

        $result = Nilvera::exportOldInvoices(['uuid-1234', 'uuid-5678'], 'Pdf');

        $this->assertSame($fileContent, $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Old/Export/Pdf'
                && $request->method() === 'POST'
                && $request->data() === ['uuid-1234', 'uuid-5678'];
        });
    }

    public function test_update_old_invoices_status(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Old/Operation/Print' => Http::response([
                'uuid-1234', 'uuid-5678',
            ], 200),
        ]);

        $result = Nilvera::updateOldInvoicesStatus(['uuid-1234', 'uuid-5678'], 'Print');

        $this->assertSame(['uuid-1234', 'uuid-5678'], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Old/Operation/Print'
                && $request->method() === 'PUT'
                && $request->data() === ['uuid-1234', 'uuid-5678'];
        });
    }
}
