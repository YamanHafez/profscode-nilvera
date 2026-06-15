<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class NotificationSettingsTest extends TestCase
{
    private function ruleData(): array
    {
        return [
            'RuleName' => 'Yüksek Tutarlı Faturalar',
            'Rules' => [
                [
                    'Property' => 'PayableAmount',
                    'Operator' => 'GreaterOrEqual',
                    'ValueFirst' => '1000',
                ],
            ],
            'Contact' => [
                [
                    'NotificationValue' => 'ornek@firma.com',
                    'NotificationValueType' => 'Mail',
                ],
            ],
            'IsActive' => true,
        ];
    }

    public function test_get_sale_notification_settings(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Notification/Sale' => Http::response([
                ['ID' => 1, 'RuleName' => 'Yüksek Tutarlı Faturalar', 'IsActive' => true],
            ], 200),
        ]);

        $result = Nilvera::getSaleNotificationSettings();

        $this->assertSame('Yüksek Tutarlı Faturalar', $result[0]['RuleName']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Notification/Sale'
                && $request->method() === 'GET';
        });
    }

    public function test_get_purchase_notification_settings(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Notification/Purchase' => Http::response([
                ['ID' => 1, 'RuleName' => 'Alış Bildirimi', 'IsActive' => true],
            ], 200),
        ]);

        $result = Nilvera::getPurchaseNotificationSettings();

        $this->assertSame('Alış Bildirimi', $result[0]['RuleName']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Notification/Purchase'
                && $request->method() === 'GET';
        });
    }

    public function test_create_sale_notification_setting(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Notification/Sale' => Http::response([
                'ID' => 1,
                'RuleName' => 'Yüksek Tutarlı Faturalar',
                'IsActive' => true,
            ], 200),
        ]);

        $result = Nilvera::createSaleNotificationSetting($this->ruleData());

        $this->assertSame('Yüksek Tutarlı Faturalar', $result['RuleName']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Notification/Sale'
                && $request->method() === 'POST'
                && $request['RuleName'] === 'Yüksek Tutarlı Faturalar'
                && $request['Rules'][0]['Property'] === 'PayableAmount';
        });
    }

    public function test_create_purchase_notification_setting(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Notification/Purchase' => Http::response([
                'ID' => 2,
                'RuleName' => 'Alış Bildirimi',
                'IsActive' => true,
            ], 200),
        ]);

        $result = Nilvera::createPurchaseNotificationSetting($this->ruleData());

        $this->assertSame('Alış Bildirimi', $result['RuleName']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Notification/Purchase'
                && $request->method() === 'POST';
        });
    }

    public function test_update_sale_notification_setting(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Notification/Sale' => Http::response('true', 200),
        ]);

        $data = $this->ruleData();
        $data['ID'] = 1;

        $result = Nilvera::updateSaleNotificationSetting($data);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Notification/Sale'
                && $request->method() === 'PUT'
                && $request['ID'] === 1;
        });
    }

    public function test_update_purchase_notification_setting(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Notification/Purchase' => Http::response('true', 200),
        ]);

        $data = $this->ruleData();
        $data['ID'] = 2;

        $result = Nilvera::updatePurchaseNotificationSetting($data);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Notification/Purchase'
                && $request->method() === 'PUT'
                && $request['ID'] === 2;
        });
    }

    public function test_delete_sale_notification_setting(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Notification/Sale/1' => Http::response('true', 200),
        ]);

        $result = Nilvera::deleteSaleNotificationSetting(1);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Notification/Sale/1'
                && $request->method() === 'DELETE';
        });
    }

    public function test_delete_purchase_notification_setting(): void
    {
        Http::fake([
            'apitest.nilvera.com/EInvoice/Notification/Purchase/2' => Http::response('true', 200),
        ]);

        $result = Nilvera::deletePurchaseNotificationSetting(2);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/EInvoice/Notification/Purchase/2'
                && $request->method() === 'DELETE';
        });
    }
}
