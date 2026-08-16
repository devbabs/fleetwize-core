<?php

namespace Tests\Feature\Jobs;

use App\Jobs\ProcessTrackerTelemetry;
use App\Models\Vehicle;
use App\Models\VehicleTrackerState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessTrackerTelemetryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The same real payload captured from a live Traccar instance used in
     * TrackerWebhookTest — see the tracker middleware spike.
     */
    protected function realPosition(): array
    {
        return [
            'attributes' => ['ignition' => true, 'battery' => 12.4],
            'latitude' => 21.398355555555554,
            'longitude' => 72.9627688888889,
            'speed' => 32.937377,
            'course' => 197.0,
            'fixTime' => '2017-06-22T09:24:53.000+00:00',
        ];
    }

    protected function realDevice(): array
    {
        return ['id' => 2, 'name' => '863586038760942', 'uniqueId' => '863586038760942'];
    }

    public function test_upserts_tracker_state_for_a_known_vehicle()
    {
        $vehicle = Vehicle::factory()->create(['obd_device_imei' => '863586038760942']);

        (new ProcessTrackerTelemetry($this->realPosition(), $this->realDevice()))->handle();

        $state = VehicleTrackerState::query()->where('vehicle_id', $vehicle->id)->first();

        $this->assertNotNull($state);
        $this->assertEqualsWithDelta(21.398355555555554, (float) $state->latitude, 0.0000001);
        $this->assertEqualsWithDelta(72.9627688888889, (float) $state->longitude, 0.0000001);
        $this->assertEqualsWithDelta(32.937377 * 1.852, $state->speed, 0.01); // knots -> km/h
        $this->assertSame(197.0, $state->heading);
        $this->assertTrue($state->ignition_on);
        $this->assertSame(12.4, $state->battery_voltage);
        $this->assertSame('2017-06-22 09:24:53', $state->reported_at->format('Y-m-d H:i:s'));
    }

    public function test_second_update_upserts_rather_than_duplicates()
    {
        $vehicle = Vehicle::factory()->create(['obd_device_imei' => '863586038760942']);

        (new ProcessTrackerTelemetry($this->realPosition(), $this->realDevice()))->handle();

        $movedPosition = array_merge($this->realPosition(), ['latitude' => 21.5, 'longitude' => 73.0]);
        (new ProcessTrackerTelemetry($movedPosition, $this->realDevice()))->handle();

        $this->assertSame(1, VehicleTrackerState::query()->where('vehicle_id', $vehicle->id)->count());
        $this->assertEqualsWithDelta(21.5, (float) $vehicle->trackerState()->first()->latitude, 0.0000001);
    }

    public function test_silently_ignores_telemetry_for_an_unknown_imei()
    {
        (new ProcessTrackerTelemetry($this->realPosition(), $this->realDevice()))->handle();

        $this->assertSame(0, VehicleTrackerState::query()->count());
    }

    public function test_silently_ignores_telemetry_with_no_device_unique_id()
    {
        Vehicle::factory()->create(['obd_device_imei' => '863586038760942']);

        (new ProcessTrackerTelemetry($this->realPosition(), ['id' => 2]))->handle();

        $this->assertSame(0, VehicleTrackerState::query()->count());
    }
}
