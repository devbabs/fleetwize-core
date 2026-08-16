<?php

namespace Database\Factories;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'make' => fake()->randomElement(['Toyota', 'Honda', 'Ford', 'Nissan']),
            'model' => fake()->word(),
            'year' => (string) fake()->numberBetween(2010, 2024),
            'color' => fake()->safeColorName(),
            'status' => 'active',
            'is_owned' => true,
            'is_active' => true,
            'obd_device_imei' => fake()->numerify('###############'),
        ];
    }
}
