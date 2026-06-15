<?php

namespace ProfsCode\Nilvera\Tests;

use Illuminate\Support\Facades\Http;
use ProfsCode\Nilvera\Facades\Nilvera;

class MailSettingsTest extends TestCase
{
    public function test_get_mail_settings(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Mailing/Setting' => Http::response([
                ['ID' => 1, 'CompanyID' => 1, 'ModuleID' => 'Portal', 'IsActive' => true],
                ['ID' => 2, 'CompanyID' => 1, 'ModuleID' => 'Sms', 'IsActive' => false],
                ['ID' => 3, 'CompanyID' => 1, 'ModuleID' => 'WhatsApp', 'IsActive' => true],
            ], 200),
        ]);

        $result = Nilvera::getMailSettings();

        $this->assertSame('Portal', $result[0]['ModuleID']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Mailing/Setting'
                && $request->method() === 'GET';
        });
    }

    public function test_set_whatsapp_setting(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Mailing/Whatsapp/Setting/true' => Http::response('true', 200),
        ]);

        $result = Nilvera::setWhatsappSetting(true);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Mailing/Whatsapp/Setting/true'
                && $request->method() === 'POST';
        });
    }

    public function test_set_mail_setting(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Mailing/Email/Setting/false' => Http::response('true', 200),
        ]);

        $result = Nilvera::setMailSetting(false);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Mailing/Email/Setting/false'
                && $request->method() === 'POST';
        });
    }

    public function test_set_sms_setting(): void
    {
        Http::fake([
            'apitest.nilvera.com/General/Mailing/Sms/Setting/true' => Http::response('true', 200),
        ]);

        $result = Nilvera::setSmsSetting(true);

        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://apitest.nilvera.com/General/Mailing/Sms/Setting/true'
                && $request->method() === 'POST';
        });
    }
}
