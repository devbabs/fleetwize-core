<?php

namespace Tests\Feature\Api\AutoX;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticated(User $user): static
    {
        return $this->withHeader('Authorization', 'Bearer '.$user->createToken('mobile')->plainTextToken);
    }

    public function test_index_only_returns_the_authenticated_users_own_vehicles()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $mine = Vehicle::factory()->for($user)->create(['make' => 'Toyota']);
        Vehicle::factory()->for($otherUser)->create(['make' => 'Honda']);

        $response = $this->authenticated($user)->getJson('/api/auto-x/vehicles');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $mine->id);
        $response->assertJsonPath('data.0.make', 'Toyota');
    }

    public function test_index_includes_online_status_for_the_vehicle_list()
    {
        $user = User::factory()->create();
        $online = Vehicle::factory()->for($user)->create();
        $online->trackerState()->create(['reported_at' => now(), 'latitude' => 1, 'longitude' => 1]);
        $offline = Vehicle::factory()->for($user)->create();
        $offline->trackerState()->create(['reported_at' => now()->subHours(2), 'latitude' => 1, 'longitude' => 1]);
        $neverReported = Vehicle::factory()->for($user)->create();

        $response = $this->authenticated($user)->getJson('/api/auto-x/vehicles');

        $response->assertOk();
        $byId = collect($response->json('data'))->keyBy('id');
        $this->assertTrue($byId[$online->id]['is_online']);
        $this->assertFalse($byId[$offline->id]['is_online']);
        $this->assertFalse($byId[$neverReported->id]['is_online']);
        $this->assertNull($byId[$neverReported->id]['last_seen_at']);
    }

    public function test_index_returns_expected_field_names_and_mileage_includes_trip_distance()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create([
            'mileage' => 1000,
            'obd_device_id' => 'objectid-123',
            'obd_device_imei' => '863586038760942',
            'tracker_phone_number' => '0551234567',
        ]);
        $vehicle->trips()->create(['distance_km' => 25.5, 'start_time' => now()]);
        $vehicle->trips()->create(['distance_km' => 4.5, 'start_time' => now()]);

        $response = $this->authenticated($user)->getJson('/api/auto-x/vehicles');

        $response->assertOk();
        $response->assertJsonPath('data.0.tracker_id', 'objectid-123');
        $response->assertJsonPath('data.0.tracker_imei', '863586038760942');
        $response->assertJsonPath('data.0.tracker_phone_number', '0551234567');
        $response->assertJsonPath('data.0.mileage', 1030);
        $response->assertJsonPath('data.0.trips_count', 2);
    }

    public function test_show_returns_403_for_a_vehicle_the_user_does_not_own()
    {
        $user = User::factory()->create();
        $otherUsersVehicle = Vehicle::factory()->for(User::factory())->create();

        $response = $this->authenticated($user)->getJson("/api/auto-x/vehicles/{$otherUsersVehicle->id}");

        $response->assertForbidden();
    }

    public function test_show_includes_trips_and_uncleared_faults()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $vehicle->trips()->create(['distance_km' => 10, 'max_engine_rpm' => 3200, 'start_time' => now()]);
        $vehicle->faults()->create(['obd_code' => 'P0300', 'meaning' => 'Random misfire', 'severity' => 3]);
        $vehicle->faults()->create(['obd_code' => 'P0171', 'cleared_at' => now()]);

        $response = $this->authenticated($user)->getJson("/api/auto-x/vehicles/{$vehicle->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data.trips');
        $response->assertJsonCount(1, 'data.alarms');
        $response->assertJsonPath('data.alarms.0.obd_code', 'P0300');
    }

    public function test_live_state_returns_404_when_no_tracker_data_exists_yet()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();

        $response = $this->authenticated($user)->getJson("/api/auto-x/vehicles/{$vehicle->id}/live-state");

        $response->assertNotFound();
    }

    public function test_live_state_returns_the_latest_tracker_state()
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->for($user)->create();
        $vehicle->trackerState()->create([
            'latitude' => 5.6,
            'longitude' => -0.2,
            'speed' => 42.5,
            'heading' => 180,
            'ignition_on' => true,
            'reported_at' => now(),
        ]);

        $response = $this->authenticated($user)->getJson("/api/auto-x/vehicles/{$vehicle->id}/live-state");

        $response->assertOk();
        $response->assertJsonPath('data.ignition_on', true);
        $response->assertJsonPath('data.is_online', true);
        $response->assertJsonPath('data.speed', 42.5);
    }
}
