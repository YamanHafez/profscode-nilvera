<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class InvoiceAnswerTest extends TestCase
{
    public function test_answer_invoice_approved(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Purchase/SendAnswer' => Http::response('"approved"', 200),
        ]);

        $result = Nilvera::answerInvoice('uuid-1234', 'approved');

        $this->assertSame('approved', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Purchase/SendAnswer'
                && $request->method() === 'POST'
                && $request['UUID'] === 'uuid-1234'
                && $request['AnswerCode'] === 'approved'
                && !array_key_exists('RejectNote', $request->data());
        });
    }

    public function test_answer_invoice_rejected_with_note(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Purchase/SendAnswer' => Http::response('"rejected"', 200),
        ]);

        $result = Nilvera::answerInvoice('uuid-5678', 'rejected', 'Hatalı fatura tutarı');

        $this->assertSame('rejected', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Purchase/SendAnswer'
                && $request->method() === 'POST'
                && $request['UUID'] === 'uuid-5678'
                && $request['AnswerCode'] === 'rejected'
                && $request['RejectNote'] === 'Hatalı fatura tutarı';
        });
    }

    public function test_get_invoice_rejected_note(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Purchase/uuid-5678/RejectedNote' => Http::response('"Hatalı fatura tutarı"', 200),
        ]);

        $result = Nilvera::getInvoiceRejectedNote('uuid-5678');

        $this->assertSame('Hatalı fatura tutarı', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Purchase/uuid-5678/RejectedNote'
                && $request->method() === 'GET';
        });
    }
}
