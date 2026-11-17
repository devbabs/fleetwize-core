<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\Tracking\TraccarService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-tracker-command')]
#[Description('Command description')]
class SendTrackerCommand extends Command
{
    protected $signature = 'tracker:send-command
                            {vehicle_id : The Fleetwize vehicle ID (e.g. 17)}
                            {command : The command string (e.g. "getver" or "setparam 11000:1")}
                            {--type=custom : Traccar command type (custom, engineStop, engineResume)}';

    protected $description = 'Send a GPRS command over TCP to a vehicle tracker via TraccarService';


    public function handle(TraccarService $traccar): int
    {
        $vehicle = Vehicle::findOrFail((int) $this->argument('vehicle_id'));

        if (! $vehicle->traccar_device_id) {
            $this->error("Vehicle {$vehicle->id} has no traccar_device_id.");

            return self::FAILURE;
        }

        $response = $traccar->sendCommand(
            (int) $vehicle->traccar_device_id,
            (string) $this->argument('command'),
            (string) $this->option('type')
        );

        $this->info("Command dispatched to Vehicle {$vehicle->id} (Traccar Device {$vehicle->traccar_device_id}):");
        $this->line(json_encode($response, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}