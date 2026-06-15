<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class InvoiceCreateFlowTest extends TestCase
{
    private function sampleData(array $overrides = []): array
    {
        return array_merge([
            'TaxNumber' => '1234567890',
            'Title' => 'Örnek Limited Şirketi',
            'Address' => 'Atatürk Cad. No:1',
            'District' => 'Çankaya',
            'City' => 'Ankara',
            'CurrencyCode' => 'TRY',
            'Series' => 'HIZ',
            'Scenario' => 'Ticari',
            'items' => [
                [
                    'Name' => 'Yazılım Hizmeti',
                    'Quantity' => 1,
                    'Price' => 5000,
                    'VatRate' => 20,
                    'UnitCode' => 'C62',
                    'Description' => 'Ocak ayı bakım bedeli',
                ],
            ],
            'Notes' => 'Fatura notu buraya yazılır.',
        ], $overrides);
    }

    public function test_create_einvoice_draft(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Draft' => Http::response([
                'UUID' => 'uuid-einvoice-1234',
            ], 200),
        ]);

        $model = Nilvera::mapToModel($this->sampleData());

        $this->assertArrayHasKey('EInvoice', $model);
        $this->assertSame(2, $model['EInvoice']['InvoiceInfo']['InvoiceProfile']);
        $this->assertSame('HIZ', $model['EInvoice']['InvoiceInfo']['InvoiceSerieOrNumber']);

        $result = Nilvera::createDraft($model, 'einvoice');

        $this->assertSame('uuid-einvoice-1234', $result['UUID']);

        Http::assertSent(function ($request) use ($model) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Draft'
                && $request->method() === 'POST'
                && $request['EInvoice']['CustomerInfo']['TaxNumber'] === $model['EInvoice']['CustomerInfo']['TaxNumber']
                && $request['EInvoice']['InvoiceLines'][0]['Name'] === 'Yazılım Hizmeti';
        });
    }

    public function test_create_earchive_draft(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Draft' => Http::response([
                'UUID' => 'uuid-earchive-1234',
            ], 200),
        ]);

        $model = Nilvera::mapToModel($this->sampleData(), isArchive: true);

        $this->assertArrayHasKey('ArchiveInvoice', $model);
        $this->assertSame('KAGIT', $model['ArchiveInvoice']['InvoiceInfo']['SendType']);

        $result = Nilvera::createDraft($model, 'earchive');

        $this->assertSame('uuid-earchive-1234', $result['UUID']);

        Http::assertSent(function ($request) use ($model) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Draft'
                && $request->method() === 'POST'
                && $request['ArchiveInvoice']['CustomerInfo']['TaxNumber'] === $model['ArchiveInvoice']['CustomerInfo']['TaxNumber'];
        });
    }

    public function test_create_return_invoice_draft(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Draft' => Http::response([
                'UUID' => 'uuid-return-1234',
            ], 200),
        ]);

        $data = $this->sampleData([
            'InvoiceType' => 'IADE',
            'ReturnInvoiceInfo' => [
                ['InvoiceNumber' => 'GIB2024000000123', 'IssueDate' => '2024-01-15'],
            ],
        ]);

        $model = Nilvera::mapToModel($data);

        $this->assertSame('IADE', $model['EInvoice']['InvoiceInfo']['InvoiceType']);
        $this->assertSame('GIB2024000000123', $model['EInvoice']['InvoiceInfo']['ReturnInvoiceInfo'][0]['InvoiceNumber']);
        $this->assertSame('2024-01-15T00:00:00', $model['EInvoice']['InvoiceInfo']['ReturnInvoiceInfo'][0]['IssueDate']);

        $result = Nilvera::createDraft($model, 'einvoice');

        $this->assertSame('uuid-return-1234', $result['UUID']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Draft'
                && $request->method() === 'POST'
                && $request['EInvoice']['InvoiceInfo']['InvoiceType'] === 'IADE'
                && $request['EInvoice']['InvoiceInfo']['ReturnInvoiceInfo'][0]['InvoiceNumber'] === 'GIB2024000000123';
        });
    }

    public function test_create_return_invoice_from_existing_invoice(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Purchase/uuid-1234/CreateReturn' => Http::response([
                'UUID' => 'uuid-return-draft-5678',
            ], 200),
        ]);

        $result = Nilvera::createReturnInvoice('uuid-1234', 'Purchase');

        $this->assertSame('uuid-return-draft-5678', $result['UUID']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Purchase/uuid-1234/CreateReturn'
                && $request->method() === 'POST';
        });
    }
}
