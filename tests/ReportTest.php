<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class ReportTest extends TestCase
{
    public function test_create_report(): void
    {
        Http::fake([
            'apitest.nilvera.com/report/Accounting' => Http::response([
                'ID' => 1,
                'UUID' => 'uuid-1234',
                'Title' => 'Aylık Rapor',
                'Status' => 'Pending',
            ], 200),
        ]);

        $result = Nilvera::createReport([
            'Title' => 'Aylık Rapor',
            'StartDate' => '2024-01-01',
            'EndDate' => '2024-01-31',
            'Type' => 1,
            'TemplateID' => 1,
            'ReportType' => 'Invoice',
        ]);

        $this->assertSame('uuid-1234', $result['UUID']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/report/Accounting'
                && $request->method() === 'POST'
                && $request['ReportType'] === 'Invoice';
        });
    }

    public function test_get_reports(): void
    {
        Http::fake([
            'apitest.nilvera.com/report/Accounting*' => Http::response([
                'Page' => 1,
                'PageSize' => 30,
                'TotalCount' => 1,
                'Content' => [
                    ['ID' => 1, 'UUID' => 'uuid-1234', 'Title' => 'Aylık Rapor', 'ReportType' => 'Invoice'],
                ],
            ], 200),
        ]);

        $result = Nilvera::getReports('Aylık', 30, 1, '2024-01-01', '2024-01-31', 'Invoice');

        $this->assertSame('Aylık Rapor', $result['Content'][0]['Title']);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://apitest.nilvera.com/report/Accounting?')
                && $request->method() === 'GET'
                && str_contains($request->url(), 'StartDate=2024-01-01')
                && str_contains($request->url(), 'EndDate=2024-01-31')
                && str_contains($request->url(), 'ReportType=Invoice');
        });
    }

    public function test_download_report(): void
    {
        Http::fake([
            'apitest.nilvera.com/report/Accounting/uuid-1234/download' => Http::response([
                'Content' => 'base64-encoded-content',
                'FileName' => 'rapor.xlsx',
            ], 200),
        ]);

        $result = Nilvera::downloadReport('uuid-1234');

        $this->assertSame('rapor.xlsx', $result['FileName']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/report/Accounting/uuid-1234/download'
                && $request->method() === 'POST';
        });
    }

    public function test_create_report_template(): void
    {
        Http::fake([
            'apitest.nilvera.com/report/Accounting/Template' => Http::response([
                'ID' => 1,
                'Name' => 'Standart Şablon',
                'ReportType' => 'Invoice',
            ], 200),
        ]);

        $result = Nilvera::createReportTemplate([
            'Name' => 'Standart Şablon',
            'Type' => 1,
            'ReportType' => 'Invoice',
            'Columns' => [
                ['Name' => 'Fatura No', 'ID' => 1],
            ],
        ]);

        $this->assertSame('Standart Şablon', $result['Name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/report/Accounting/Template'
                && $request->method() === 'POST'
                && $request['ReportType'] === 'Invoice';
        });
    }

    public function test_get_report_templates(): void
    {
        Http::fake([
            'apitest.nilvera.com/report/Accounting/Template' => Http::response([
                'Page' => 1,
                'PageSize' => 30,
                'TotalCount' => 1,
                'Content' => [
                    ['ID' => 1, 'Name' => 'Standart Şablon', 'ReportType' => 'Invoice'],
                ],
            ], 200),
        ]);

        $result = Nilvera::getReportTemplates();

        $this->assertSame('Standart Şablon', $result['Content'][0]['Name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/report/Accounting/Template'
                && $request->method() === 'GET';
        });
    }
}
