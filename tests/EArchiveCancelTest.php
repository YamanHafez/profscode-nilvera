<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class EArchiveCancelTest extends TestCase
{
    public function test_revert_cancel_earchive_invoice(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Invoices/uuid-1234/RevertCancel' => Http::response('true', 200),
        ]);

        $result = Nilvera::revertCancelEArchiveInvoice('uuid-1234');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Invoices/uuid-1234/RevertCancel'
                && $request->method() === 'PUT';
        });
    }
}
