<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class TagsCrudTest extends TestCase
{
    public function test_create_tag(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Tags' => Http::response([
                'UUID' => 'uuid-1234',
                'Name' => 'Önemli',
                'Description' => 'Önemli faturalar',
                'Color' => '#FF0000',
            ], 200),
        ]);

        $result = Nilvera::createTag('Önemli', 'Önemli faturalar', '#FF0000');

        $this->assertSame('uuid-1234', $result['UUID']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Tags'
                && $request->method() === 'POST'
                && $request['Name'] === 'Önemli'
                && $request['Description'] === 'Önemli faturalar'
                && $request['Color'] === '#FF0000';
        });
    }

    public function test_update_tag(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Tags' => Http::response('true', 200),
        ]);

        $result = Nilvera::updateTag('uuid-1234', name: 'Güncellenmiş Etiket', color: '#00FF00');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Tags'
                && $request->method() === 'PUT'
                && $request['UUID'] === 'uuid-1234'
                && $request['Name'] === 'Güncellenmiş Etiket'
                && $request['Color'] === '#00FF00'
                && !array_key_exists('Description', $request->data());
        });
    }

    public function test_delete_tag(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Tags/uuid-1234' => Http::response('true', 200),
        ]);

        $result = Nilvera::deleteTag('uuid-1234');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Tags/uuid-1234'
                && $request->method() === 'DELETE';
        });
    }

    public function test_create_tag_for_earchive(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Tags' => Http::response([
                'UUID' => 'uuid-5678',
                'Name' => 'Acil',
            ], 200),
        ]);

        $result = Nilvera::createTag('Acil', mode: 'earchive');

        $this->assertSame('uuid-5678', $result['UUID']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Tags'
                && $request->method() === 'POST'
                && $request['Name'] === 'Acil'
                && !array_key_exists('Description', $request->data())
                && !array_key_exists('Color', $request->data());
        });
    }
}
