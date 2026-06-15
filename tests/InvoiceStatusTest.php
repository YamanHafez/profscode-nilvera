<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class InvoiceStatusTest extends TestCase
{
    public function test_get_invoice_status_for_einvoice_sale(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Sale/uuid-1234/Status' => Http::response([
                'StatusCode' => 'succeed',
                'StatusDetail' => null,
                'ReportStatus' => 'Reported',
                'CancelStatus' => false,
            ], 200),
        ]);

        $result = Nilvera::getInvoiceStatus('uuid-1234');

        $this->assertSame('succeed', $result['StatusCode']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Sale/uuid-1234/Status'
                && $request->method() === 'GET';
        });
    }

    public function test_get_invoice_status_for_einvoice_purchase(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Purchase/uuid-5678/Status' => Http::response([
                'StatusCode' => 'waiting',
                'ReportStatus' => 'NotReported',
                'CancelStatus' => false,
            ], 200),
        ]);

        $result = Nilvera::getInvoiceStatus('uuid-5678', 'Purchase');

        $this->assertSame('waiting', $result['StatusCode']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Purchase/uuid-5678/Status'
                && $request->method() === 'GET';
        });
    }

    public function test_get_invoice_status_for_earchive(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Invoices/uuid-9999/Status' => Http::response([
                'StatusCode' => 'succeed',
                'ReportStatus' => 'Reported',
                'CancelStatus' => false,
            ], 200),
        ]);

        $result = Nilvera::getInvoiceStatus('uuid-9999', 'Sale', 'earchive');

        $this->assertSame('succeed', $result['StatusCode']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Invoices/uuid-9999/Status'
                && $request->method() === 'GET';
        });
    }
}
