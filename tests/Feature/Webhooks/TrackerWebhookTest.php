<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\ProcessTrackerTelemetry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class TrackerWebhookTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A real payload captured from a live Traccar instance forwarding a
     * decoded WanWay/GT06 position (forward.type=json), not a hand-written
     * fixture — see the tracker middleware spike.
     */
    protected function realTraccarPayload(): array
    {
        return [
            'position' => [
                'id' => 0,
                'attributes' => [
                    'type' => 18,
                    'sat' => 0,
                    'ignition' => true,
                    'distance' => 0.0,
                    'totalDistance' => 0.0,
                    'motion' => true,
                ],
                'deviceId' => 2,
                'protocol' => 'gt06',
                'serverTime' => '2026-08-15T10:50:16.546+00:00',
                'deviceTime' => '2017-06-22T09:24:53.000+00:00',
                'fixTime' => '2017-06-22T09:24:53.000+00:00',
                'valid' => true,
                'latitude' => 21.398355555555554,
                'longitude' => 72.9627688888889,
                'altitude' => 0.0,
                'speed' => 32.937377,
                'course' => 197.0,
            ],
            'device' => [
                'id' => 2,
                'name' => '863586038760942',
                'uniqueId' => '863586038760942',
                'status' => 'offline',
            ],
        ];
    }

    public function test_rejects_requests_without_the_shared_secret()
    {
        config(['fleetwize.traccar.webhook_secret' => 'expected-secret']);

        $response = $this->postJson(route('webhooks.traccar.position'), $this->realTraccarPayload());

        $response->assertUnauthorized();
    }

    public function test_rejects_requests_with_the_wrong_secret()
    {
        config(['fleetwize.traccar.webhook_secret' => 'expected-secret']);

        $response = $this->postJson(
            route('webhooks.traccar.position'),
            $this->realTraccarPayload(),
            ['X-Webhook-Secret' => 'wrong-secret'],
        );

        $response->assertUnauthorized();
    }

    public function test_accepts_a_real_traccar_forwarded_payload_and_dispatches_the_job()
    {
        config(['fleetwize.traccar.webhook_secret' => 'expected-secret']);
        Bus::fake();

        $payload = $this->realTraccarPayload();

        $response = $this->postJson(
            route('webhooks.traccar.position'),
            $payload,
            ['X-Webhook-Secret' => 'expected-secret'],
        );

        $response->assertNoContent();

        Bus::assertDispatched(
            ProcessTrackerTelemetry::class,
            fn (ProcessTrackerTelemetry $job) => $job->device['uniqueId'] === '863586038760942'
                && $job->position['latitude'] === 21.398355555555554,
        );
    }

    public function test_rejects_malformed_payloads()
    {
        config(['fleetwize.traccar.webhook_secret' => 'expected-secret']);

        $response = $this->postJson(
            route('webhooks.traccar.position'),
            ['position' => ['latitude' => 1.0]],
            ['X-Webhook-Secret' => 'expected-secret'],
        );

        $response->assertUnprocessable();
    }
}
