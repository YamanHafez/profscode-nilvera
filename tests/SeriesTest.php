<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class SeriesTest extends TestCase
{
    public function test_update_series(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Series' => Http::response('true', 200),
        ]);

        $result = Nilvera::updateSeries(1, isActive: true, isDefault: false);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Series'
                && $request->method() === 'PUT'
                && $request['ID'] === 1
                && $request['IsActive'] === true
                && $request['IsDefault'] === false;
        });
    }

    public function test_get_series_detail(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Series/1' => Http::response([
                'ID' => 1,
                'Name' => 'HIZ',
                'Details' => [
                    ['ID' => 1, 'Year' => 2024, 'OrdinalNumber' => 100, 'LastIssueDate' => '2024-05-01'],
                ],
            ], 200),
        ]);

        $result = Nilvera::getSeriesDetail(1);

        $this->assertSame('HIZ', $result['Name']);
        $this->assertSame(2024, $result['Details'][0]['Year']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Series/1'
                && $request->method() === 'GET';
        });
    }

    public function test_update_series_for_earchive(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Series' => Http::response('true', 200),
        ]);

        $result = Nilvera::updateSeries(2, isActive: false, mode: 'earchive');

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Series'
                && $request->method() === 'PUT'
                && $request['ID'] === 2
                && $request['IsActive'] === false
                && !array_key_exists('IsDefault', $request->data());
        });
    }
}
