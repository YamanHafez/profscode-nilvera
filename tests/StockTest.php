<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class StockTest extends TestCase
{
    public function test_get_stocks(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Stocks*' => Http::response([
                'Page' => 1,
                'PageSize' => 30,
                'TotalCount' => 1,
                'Content' => [
                    ['ID' => 1, 'Name' => 'Ürün A', 'Price' => 100, 'IsActive' => true],
                ],
            ], 200),
        ]);

        $result = Nilvera::getStocks('Ürün', 30, 1, 'Name', 'ASC', true);

        $this->assertSame('Ürün A', $result['Content'][0]['Name']);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://apitest.nilvera.com/General/Stocks?')
                && $request->method() === 'GET'
                && str_contains($request->url(), 'Search=')
                && str_contains($request->url(), 'SortColumn=Name')
                && str_contains($request->url(), 'SortType=ASC')
                && str_contains($request->url(), 'IsActive=true');
        });
    }

    public function test_create_stocks(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Stocks' => Http::response('"Success"', 200),
        ]);

        $result = Nilvera::createStocks([
            'Name' => 'Ürün A',
            'UnitCode' => 'C62',
            'Price' => 100,
            'IsActive' => true,
            'TaxPercent' => 20,
        ]);

        $this->assertSame('Success', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Stocks'
                && $request->method() === 'POST'
                && $request->data() === [[
                    'Name' => 'Ürün A',
                    'UnitCode' => 'C62',
                    'Price' => 100,
                    'IsActive' => true,
                    'TaxPercent' => 20,
                ]];
        });
    }

    public function test_update_stock(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Stocks' => Http::response('true', 200),
        ]);

        $result = Nilvera::updateStock([
            'ID' => 1,
            'Name' => 'Ürün A Güncel',
            'Price' => 150,
        ]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Stocks'
                && $request->method() === 'PUT'
                && $request['ID'] === 1;
        });
    }

    public function test_get_stock_by_id(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Stocks/1' => Http::response([
                'ID' => 1,
                'Name' => 'Ürün A',
                'Price' => 100,
            ], 200),
        ]);

        $result = Nilvera::getStockById(1);

        $this->assertSame('Ürün A', $result['Name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Stocks/1'
                && $request->method() === 'GET';
        });
    }

    public function test_delete_stock(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Stocks/1' => Http::response('true', 200),
        ]);

        $result = Nilvera::deleteStock(1);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Stocks/1'
                && $request->method() === 'DELETE';
        });
    }

    public function test_bulk_delete_stocks(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Stocks/Bulk' => Http::response('true', 200),
        ]);

        $result = Nilvera::bulkDeleteStocks([1, 2, 3]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Stocks/Bulk'
                && $request->method() === 'DELETE'
                && $request->body() === json_encode([1, 2, 3]);
        });
    }

    public function test_search_stocks(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Stocks/SearchStock/*' => Http::response([
                ['ID' => 1, 'Name' => 'Ürün A', 'Price' => 100],
            ], 200),
        ]);

        $result = Nilvera::searchStocks('Ürün');

        $this->assertSame('Ürün A', $result[0]['Name']);

        Http::assertSent(function ($request) {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://apitest.nilvera.com/General/Stocks/SearchStock/');
        });
    }
}
