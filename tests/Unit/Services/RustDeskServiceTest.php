<?php

namespace Tests\Unit\Services;

use App\Services\RustDeskService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RustDeskServiceTest extends TestCase
{
    protected RustDeskService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('rustdesk.api_url', 'https://rustdesk.local');
        config()->set('rustdesk.api_token', 'test-token');
        config()->set('rustdesk.endpoints.device_status', '/api/devices/{id}');
        config()->set('rustdesk.endpoints.devices', '/api/devices');
        config()->set('rustdesk.endpoints.sync_alias', '/api/devices/{id}/alias');
        config()->set('rustdesk.alias_method', 'patch');
        config()->set('rustdesk.retry_times', 0);

        $this->service = app(RustDeskService::class);
    }

    public function test_get_device_status_returns_success(): void
    {
        Http::fake([
            'https://rustdesk.local/api/devices/123456' => Http::response([
                'id' => '123456',
                'online' => true,
            ], 200),
        ]);

        $result = $this->service->getDeviceStatus('123456');

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['data']['online']);
    }

    public function test_get_device_status_handles_external_error(): void
    {
        Http::fake([
            'https://rustdesk.local/api/devices/123456' => Http::response([
                'message' => 'Unauthorized',
            ], 401),
        ]);

        $result = $this->service->getDeviceStatus('123456');

        $this->assertFalse($result['ok']);
        $this->assertSame(401, $result['status']);
        $this->assertNotEmpty($result['error']);
    }

    public function test_list_devices_sends_search_parameter(): void
    {
        Http::fake([
            'https://rustdesk.local/api/devices*' => Http::response([
                'data' => [
                    ['id' => '1', 'online' => true],
                ],
            ], 200),
        ]);

        $result = $this->service->listDevices('cliente-a');

        $this->assertTrue($result['ok']);
        $this->assertCount(1, $result['data']['devices']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'search=cliente-a');
        });
    }

    public function test_sync_alias_fallbacks_to_put_when_patch_is_not_allowed(): void
    {
        Http::fake(function ($request) {
            if ($request->method() === 'PATCH') {
                return Http::response([], 405);
            }

            if ($request->method() === 'PUT') {
                return Http::response(['ok' => true], 200);
            }

            return Http::response([], 500);
        });

        $ok = $this->service->syncAlias('123456', 'PC-ALIAS');

        $this->assertTrue($ok);
        Http::assertSentCount(2);
    }
}
