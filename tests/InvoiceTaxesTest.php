<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class InvoiceTaxesTest extends TestCase
{
    public function test_get_invoice_taxes_for_einvoice_sale(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Sale/uuid-1234/Taxes' => Http::response([
                ['TaxableAmount' => 1000.0, 'TaxAmount' => 180.0, 'Percent' => 18.0, 'TaxTypeCode' => 'KDV'],
            ], 200),
        ]);

        $result = Nilvera::getInvoiceTaxes('uuid-1234');

        $this->assertSame('KDV', $result[0]['TaxTypeCode']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Sale/uuid-1234/Taxes'
                && $request->method() === 'GET';
        });
    }

    public function test_get_invoice_taxes_for_einvoice_purchase(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Purchase/uuid-5678/Taxes' => Http::response([
                ['TaxableAmount' => 500.0, 'TaxAmount' => 90.0, 'Percent' => 18.0, 'TaxTypeCode' => 'KDV'],
            ], 200),
        ]);

        $result = Nilvera::getInvoiceTaxes('uuid-5678', 'Purchase');

        $this->assertEquals(90.0, $result[0]['TaxAmount']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Purchase/uuid-5678/Taxes'
                && $request->method() === 'GET';
        });
    }

    public function test_get_invoice_taxes_for_earchive(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Invoices/uuid-9999/Taxes' => Http::response([
                ['TaxableAmount' => 2000.0, 'TaxAmount' => 360.0, 'Percent' => 18.0, 'TaxTypeCode' => 'KDV'],
            ], 200),
        ]);

        $result = Nilvera::getInvoiceTaxes('uuid-9999', 'Sale', 'earchive');

        $this->assertEquals(360.0, $result[0]['TaxAmount']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Invoices/uuid-9999/Taxes'
                && $request->method() === 'GET';
        });
    }
}
