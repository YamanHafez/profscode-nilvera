<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class CampaignTest extends TestCase
{
    public function test_get_campaigns(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Campaigns' => Http::response([
                [
                    'Name' => 'Yıl Sonu Kampanyası',
                    'CreatedDate' => '2026-01-01T00:00:00',
                    'EndDate' => '2026-12-31T00:00:00',
                    'Content' => 'Kampanya içeriği',
                    'Subject' => 'Kampanya',
                    'IsActive' => true,
                    'Url' => 'https://nilvera.com/kampanya',
                ],
            ], 200),
        ]);

        $result = Nilvera::getCampaigns();

        $this->assertSame('Yıl Sonu Kampanyası', $result[0]['Name']);
        $this->assertTrue($result[0]['IsActive']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Campaigns'
                && $request->method() === 'GET';
        });
    }
}
