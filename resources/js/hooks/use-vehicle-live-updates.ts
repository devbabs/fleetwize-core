import { usePage } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';

/**
 * Payload shape broadcast by App\Events\VehicleTrackerStateUpdated. One
 * private channel per company carries every vehicle's updates — callers
 * filter to whichever vehicle(s) they care about.
 */
export type VehicleLiveUpdate = {
    id: number;
    name: string | null;
    licensePlate: string | null;
    category: string;
    isOnline: boolean;
    latitude: number | null;
    longitude: number | null;
    speed: number | null;
    heading: number | null;
    ignitionOn: boolean | null;
    fuelLevel: number | null;
    batteryVoltage: number | null;
    reportedAt: string | null;
};

/**
 * Subscribes to the current company's live vehicle-telemetry channel.
 * Used by the Live Tracking, Vehicles list, and Vehicle detail pages so a
 * tracker update reaches whichever of those happen to be open, without a
 * refresh or poll. Subscribe/unsubscribe lifecycle is handled by useEcho.
 */
export function useVehicleLiveUpdates(onUpdate: (update: VehicleLiveUpdate) => void) {
    const { company } = usePage().props;

    useEcho<VehicleLiveUpdate>(`company.${company?.id}.vehicles`, '.vehicle.updated', onUpdate, [company?.id]);
}
