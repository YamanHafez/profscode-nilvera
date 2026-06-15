<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class EArchiveReportTest extends TestCase
{
    public function test_send_earchive_report(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Send/Report' => Http::response([
                'UUID' => 'uuid-1234',
            ], 200),
        ]);

        $result = Nilvera::sendEArchiveReport(2024, 1);

        $this->assertSame('uuid-1234', $result['UUID']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Send/Report'
                && $request->method() === 'POST'
                && $request['PeriodYear'] === 2024
                && $request['PeriodMonth'] === 1;
        });
    }

    public function test_get_earchive_reports(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Report*' => Http::response([
                'Page' => 1,
                'PageSize' => 30,
                'TotalCount' => 1,
                'Content' => [
                    ['UUID' => 'uuid-1234', 'PeriodYear' => 2024, 'PeriodMonth' => 1],
                ],
            ], 200),
        ]);

        $result = Nilvera::getEArchiveReports(30, 1, 'PeriodYear', 'DESC', 2024, 1);

        $this->assertSame('uuid-1234', $result['Content'][0]['UUID']);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://apitest.nilvera.com/EArchive/Report?')
                && $request->method() === 'GET'
                && str_contains($request->url(), 'SortColumn=PeriodYear')
                && str_contains($request->url(), 'PeriodYear=2024')
                && str_contains($request->url(), 'PeriodMonth=1');
        });
    }

    public function test_get_earchive_reportable_documents(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Report/ToReport*' => Http::response([
                'Page' => 1,
                'PageSize' => 30,
                'TotalCount' => 1,
                'Content' => [
                    ['UUID' => 'uuid-5678', 'InvoiceNumber' => 'GIB2024000000001'],
                ],
            ], 200),
        ]);

        $result = Nilvera::getEArchiveReportableDocuments(30, 1, 'GIB', 2024, 1);

        $this->assertSame('uuid-5678', $result['Content'][0]['UUID']);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://apitest.nilvera.com/EArchive/Report/ToReport?')
                && $request->method() === 'GET'
                && str_contains($request->url(), 'Search=GIB')
                && str_contains($request->url(), 'PeriodYear=2024')
                && str_contains($request->url(), 'PeriodMonth=1');
        });
    }

    public function test_get_earchive_report_documents(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Report/List*' => Http::response([
                'Page' => 1,
                'PageSize' => 30,
                'TotalCount' => 1,
                'Content' => [
                    ['UUID' => 'uuid-9999', 'InvoiceNumber' => 'GIB2024000000002'],
                ],
            ], 200),
        ]);

        $result = Nilvera::getEArchiveReportDocuments('uuid-1234');

        $this->assertSame('uuid-9999', $result['Content'][0]['UUID']);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://apitest.nilvera.com/EArchive/Report/List?')
                && $request->method() === 'GET'
                && str_contains($request->url(), 'UUID=uuid-1234');
        });
    }

    public function test_download_earchive_report_xml(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Report/uuid-1234/xml' => Http::response('<xml>rapor</xml>', 200),
        ]);

        $result = Nilvera::downloadEArchiveReportXml('uuid-1234');

        $this->assertSame('<xml>rapor</xml>', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Report/uuid-1234/xml'
                && $request->method() === 'GET';
        });
    }

    public function test_get_earchive_report_histories(): void
    {
        Http::fake([
            'apitest.nilvera.com/EArchive/Report/uuid-1234/Histories' => Http::response([
                ['Status' => 'Sent', 'Date' => '2024-02-01'],
            ], 200),
        ]);

        $result = Nilvera::getEArchiveReportHistories('uuid-1234');

        $this->assertSame('Sent', $result[0]['Status']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EArchive/Report/uuid-1234/Histories'
                && $request->method() === 'GET';
        });
    }
}
