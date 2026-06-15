<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class DraftStatusTest extends TestCase
{
    public function test_update_drafts_status_to_print(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Draft/Operation/Print' => Http::response([
                'uuid-1', 'uuid-2',
            ], 200),
        ]);

        $result = Nilvera::updateDraftsStatus(['uuid-1', 'uuid-2'], 'Print');

        $this->assertSame(['uuid-1', 'uuid-2'], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Draft/Operation/Print'
                && $request->method() === 'PUT'
                && $request->body() === json_encode(['uuid-1', 'uuid-2']);
        });
    }

    public function test_update_drafts_status_to_unprint(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Draft/Operation/UnPrint' => Http::response([
                'uuid-3',
            ], 200),
        ]);

        $result = Nilvera::updateDraftsStatus(['uuid-3'], 'UnPrint');

        $this->assertSame(['uuid-3'], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Draft/Operation/UnPrint'
                && $request->method() === 'PUT';
        });
    }

    public function test_update_drafts_status_for_earchive(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Draft/Operation/Transferred' => Http::response([
                'uuid-4',
            ], 200),
        ]);

        $result = Nilvera::updateDraftsStatus(['uuid-4'], 'Transferred', 'earchive');

        $this->assertSame(['uuid-4'], $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Draft/Operation/Transferred'
                && $request->method() === 'PUT';
        });
    }
}
