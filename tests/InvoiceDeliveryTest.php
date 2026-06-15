<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class InvoiceDeliveryTest extends TestCase
{
    public function test_send_invoice_email(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Invoices/Email/Send' => Http::response([
                'MessageId' => 'msg-1234',
            ], 200),
        ]);

        $result = Nilvera::sendInvoiceEmail('uuid-1234', ['ornek@firma.com']);

        $this->assertSame('msg-1234', $result['MessageId']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Invoices/Email/Send'
                && $request->method() === 'POST'
                && $request['UUID'] === 'uuid-1234'
                && $request['EmailAddresses'] === ['ornek@firma.com'];
        });
    }

    public function test_send_invoice_sms(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Invoices/Sms/Send' => Http::response('true', 200),
        ]);

        $result = Nilvera::sendInvoiceSms('uuid-1234', '905551234567');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Invoices/Sms/Send'
                && $request->method() === 'POST'
                && $request['UUID'] === 'uuid-1234'
                && $request['Phone'] === '905551234567';
        });
    }

    public function test_get_mail_activity_histories(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Invoices/msg-1234/MailActivityhistories' => Http::response([
                ['Event' => 'delivered', 'Date' => '2024-01-01'],
            ], 200),
        ]);

        $result = Nilvera::getMailActivityHistories('msg-1234');

        $this->assertSame('delivered', $result[0]['Event']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Invoices/msg-1234/MailActivityhistories'
                && $request->method() === 'GET';
        });
    }

    public function test_get_sms_histories(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Invoices/uuid-1234/Smshistories' => Http::response([
                ['Phone' => '905551234567', 'Status' => 'Sent'],
            ], 200),
        ]);

        $result = Nilvera::getSmsHistories('uuid-1234');

        $this->assertSame('Sent', $result[0]['Status']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Invoices/uuid-1234/Smshistories'
                && $request->method() === 'GET';
        });
    }

    public function test_get_whatsapp_histories(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Invoices/uuid-1234/Whatsapphistories' => Http::response([
                ['Phone' => '905551234567', 'Status' => 'Sent'],
            ], 200),
        ]);

        $result = Nilvera::getWhatsappHistories('uuid-1234');

        $this->assertSame('Sent', $result[0]['Status']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Invoices/uuid-1234/Whatsapphistories'
                && $request->method() === 'GET';
        });
    }
}
