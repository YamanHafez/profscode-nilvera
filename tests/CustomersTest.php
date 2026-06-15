<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class CustomersTest extends TestCase
{
    public function test_get_customers(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Customers*' => Http::response([
                'Page' => 1,
                'PageSize' => 30,
                'TotalCount' => 1,
                'Content' => [
                    ['ID' => 1, 'Name' => 'Müşteri Ünvanı', 'TaxNumber' => '1234567890'],
                ],
            ], 200),
        ]);

        $result = Nilvera::getCustomers('Müşteri', 30, 1, 'Name', 'ASC');

        $this->assertSame('Müşteri Ünvanı', $result['Content'][0]['Name']);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://apitest.nilvera.com/General/Customers?')
                && $request->method() === 'GET'
                && str_contains($request->url(), 'Search=')
                && str_contains($request->url(), 'SortColumn=Name')
                && str_contains($request->url(), 'SortType=ASC');
        });
    }

    public function test_update_customer(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Customers' => Http::response('true', 200),
        ]);

        $result = Nilvera::updateCustomer([
            'ID' => 1,
            'Name' => 'Güncellenmiş Ünvan',
        ]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Customers'
                && $request->method() === 'PUT'
                && $request['ID'] === 1;
        });
    }

    public function test_get_customer_by_id(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Customers/1' => Http::response([
                'ID' => 1,
                'Name' => 'Müşteri Ünvanı',
                'TaxNumber' => '1234567890',
            ], 200),
        ]);

        $result = Nilvera::getCustomerById(1);

        $this->assertSame('Müşteri Ünvanı', $result['Name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Customers/1'
                && $request->method() === 'GET';
        });
    }

    public function test_delete_customer(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Customers/1' => Http::response('true', 200),
        ]);

        $result = Nilvera::deleteCustomer(1);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Customers/1'
                && $request->method() === 'DELETE';
        });
    }

    public function test_bulk_delete_customers(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Customers/Bulk' => Http::response('true', 200),
        ]);

        $result = Nilvera::bulkDeleteCustomers([1, 2, 3]);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Customers/Bulk'
                && $request->method() === 'DELETE'
                && $request->body() === json_encode([1, 2, 3]);
        });
    }

    public function test_search_customers(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Customers/Search/*' => Http::response([
                ['ID' => 1, 'Name' => 'Müşteri Ünvanı', 'TaxNumber' => '1234567890'],
            ], 200),
        ]);

        $result = Nilvera::searchCustomers('Müşteri');

        $this->assertSame('Müşteri Ünvanı', $result[0]['Name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Customers/Search/M%C3%BC%C5%9Fteri'
                && $request->method() === 'GET';
        });
    }

    public function test_get_customer_by_tax_number(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Customers/GetCustomerInfo/1234567890' => Http::response([
                'ID' => 1,
                'Name' => 'Müşteri Ünvanı',
                'TaxNumber' => '1234567890',
            ], 200),
        ]);

        $result = Nilvera::getCustomerByTaxNumber('1234567890');

        $this->assertSame('1234567890', $result['TaxNumber']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Customers/GetCustomerInfo/1234567890'
                && $request->method() === 'GET';
        });
    }
}
