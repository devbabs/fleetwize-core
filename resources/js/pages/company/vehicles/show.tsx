import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useVehicleLiveUpdates } from '@/hooks/use-vehicle-live-updates';
import CompanyLayout from '@/layouts/company/company-layout';
import { cn } from '@/lib/utils';

type Trip = {
    id: number;
    startTime: string | null;
    endTime: string | null;
    distanceKm: number | null;
    averageSpeed: number | null;
    maxSpeed: number | null;
    fuelConsumed: number | null;
    startOdometer: number | null;
    endOdometer: number | null;
    startLatitude: number | null;
    startLongitude: number | null;
    endLatitude: number | null;
    endLongitude: number | null;
    startAddress: string | null;
    endAddress: string | null;
    driverUniqueId: string | null;
    driverName: string | null;
};

type Fault = {
    id: number;
    code: string | null;
    meaning: string | null;
    severity: number | null;
    logTime: string | null;
    clearedAt: string | null;
};

type VehicleDocument = {
    id: number;
    title: string | null;
    expiresAt: string | null;
    expiryStatus: string | null;
};

type ServiceEntry = {
    id: number;
    startsAt: string | null;
    endsAt: string | null;
    comments: string | null;
};

type Issue = {
    id: number;
    summary: string;
    priority: string;
    status: string;
    reportedAt: string | null;
};

type VehicleDetail = {
    id: number;
    name: string | null;
    licensePlate: string | null;
    make: string | null;
    model: string | null;
    year: string | null;
    color: string | null;
    category: string;
    status: string;
    mileage: number;
    vin: string | null;
    trackerImei: string | null;
    trackerPhoneNumber: string | null;
    isOnline: boolean;
    liveState: {
        latitude: number | null;
        longitude: number | null;
        speed: number | null;
        heading: number | null;
        ignitionOn: boolean | null;
        fuelLevel: number | null;
        batteryVoltage: number | null;
        reportedAt: string | null;
        engineRpm: number | null;
        engineLoad: number | null;
        obdSpeed: number | null;
        isMoving: boolean | null;
        batteryLevel: number | null;
        satelliteCount: number | null;
        signalStrength: number | null;
        engineHours: number | null;
        isBlocked: boolean | null;
        isCharging: boolean | null;
    } | null;
    trips: Trip[];
    faults: Fault[];
    alarms: Fault[];
    documents: VehicleDocument[];
    serviceEntries: ServiceEntry[];
    issues: Issue[];
};

const tabs = ['Overview', 'Trip History', 'Maintenance', 'Documents', 'Issues'] as const;
type Tab = (typeof tabs)[number];

function formatDateTime(value: string | null) {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

export default function VehicleShow({ vehicle: initialVehicle }: { vehicle: VehicleDetail }) {
    const [tab, setTab] = useState<Tab>('Overview');
    const [vehicle, setVehicle] = useState(initialVehicle);
    const [selectedTrip, setSelectedTrip] = useState<Trip | null>(null);

    // The channel carries every vehicle in the company — filter to this one.
    useVehicleLiveUpdates((update) => {
        if (update.id !== vehicle.id) {
            return;
        }

        setVehicle((prev) => ({
            ...prev,
            isOnline: update.isOnline,
            liveState: {
                latitude: update.latitude,
                longitude: update.longitude,
                speed: update.speed,
                heading: update.heading,
                ignitionOn: update.ignitionOn,
                fuelLevel: update.fuelLevel,
                batteryVoltage: update.batteryVoltage,
                reportedAt: update.reportedAt,
                engineRpm: update.engineRpm,
                engineLoad: update.engineLoad,
                obdSpeed: update.obdSpeed,
                isMoving: update.isMoving,
                batteryLevel: update.batteryLevel,
                satelliteCount: update.satelliteCount,
                signalStrength: update.signalStrength,
                engineHours: update.engineHours,
                isBlocked: update.isBlocked,
                isCharging: update.isCharging,
            },
        }));
    });

    // Faults (manual OBD workshop scans) and alarms (live tracker events)
    // are separate underlying tables — their ids can collide, so tag each
    // with its source for a stable key before merging into one list of
    // "something's open for this vehicle".
    const openAlerts = [
        ...vehicle.faults.map((fault) => ({ ...fault, key: `fault-${fault.id}` })),
        ...vehicle.alarms.map((alarm) => ({ ...alarm, key: `alarm-${alarm.id}` })),
    ]
        .filter((alert) => !alert.clearedAt)
        .sort((a, b) => (b.logTime ?? '').localeCompare(a.logTime ?? ''));

    return (
        <CompanyLayout title={vehicle.licensePlate ?? 'Vehicle'}>
            <Head title={vehicle.licensePlate ?? 'Vehicle'} />

            <Link href="/vehicles" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft className="size-4" /> Back to vehicles
            </Link>

            <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-semibold text-foreground">{vehicle.licensePlate ?? vehicle.name}</h2>
                    <p className="text-sm text-muted-foreground">
                        {[vehicle.year, vehicle.make, vehicle.model].filter(Boolean).join(' ')}
                        {vehicle.color ? ` · ${vehicle.color}` : ''}
                    </p>
                </div>
                {vehicle.isOnline ? (
                    <Badge className="border-transparent bg-brand-green/15 text-brand-green">
                        {(vehicle.liveState?.speed ?? 0) > 5 ? 'Moving' : 'Online'}
                    </Badge>
                ) : (
                    <Badge variant="outline">Offline</Badge>
                )}
            </div>

            <div className="flex gap-1 border-b border-border">
                {tabs.map((t) => (
                    <button
                        key={t}
                        onClick={() => setTab(t)}
                        className={cn(
                            'border-b-2 px-4 py-2 text-sm font-medium transition-colors',
                            tab === t
                                ? 'border-brand-navy text-brand-navy dark:border-brand-green dark:text-brand-green'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {t}
                    </button>
                ))}
            </div>

            {/* {tab === 'Overview' ? (
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Current Fuel Level</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.fuelLevel !== null && vehicle.liveState?.fuelLevel !== undefined
                                    ? `${Math.round(vehicle.liveState.fuelLevel)}%`
                                    : '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Ignition</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.ignitionOn === null || vehicle.liveState?.ignitionOn === undefined
                                    ? '—'
                                    : vehicle.liveState.ignitionOn
                                      ? 'On'
                                      : 'Off'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Last Updated</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm font-medium text-foreground">{formatDateTime(vehicle.liveState?.reportedAt ?? null)}</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Engine RPM</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.engineRpm !== null && vehicle.liveState?.engineRpm !== undefined
                                    ? vehicle.liveState.engineRpm.toLocaleString()
                                    : '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Engine Load</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.engineLoad !== null && vehicle.liveState?.engineLoad !== undefined
                                    ? `${Math.round(vehicle.liveState.engineLoad)}%`
                                    : '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">OBD Speed</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.obdSpeed !== null && vehicle.liveState?.obdSpeed !== undefined
                                    ? `${Math.round(vehicle.liveState.obdSpeed)} km/h`
                                    : '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Motion</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.isMoving === null || vehicle.liveState?.isMoving === undefined
                                    ? '—'
                                    : vehicle.liveState.isMoving
                                      ? 'Moving'
                                      : 'Stationary'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Battery Level</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.batteryLevel !== null && vehicle.liveState?.batteryLevel !== undefined
                                    ? `${Math.round(vehicle.liveState.batteryLevel)}%`
                                    : '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Satellites</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.satelliteCount ?? '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Signal Strength</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.signalStrength ?? '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Engine Hours</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.engineHours !== null && vehicle.liveState?.engineHours !== undefined
                                    ? vehicle.liveState.engineHours.toLocaleString()
                                    : '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Immobilizer</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.isBlocked === null || vehicle.liveState?.isBlocked === undefined
                                    ? '—'
                                    : vehicle.liveState.isBlocked
                                      ? 'Blocked'
                                      : 'Clear'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Charging</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.isCharging === null || vehicle.liveState?.isCharging === undefined
                                    ? '—'
                                    : vehicle.liveState.isCharging
                                      ? 'Charging'
                                      : 'Not charging'}
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="lg:col-span-3">
                        <CardHeader>
                            <CardTitle>Open Alerts</CardTitle>
                        </CardHeader>
                        <CardContent className="px-0">
                            {openAlerts.length === 0 ? (
                                <p className="px-6 text-sm text-muted-foreground">No open alerts.</p>
                            ) : (
                                <div className="divide-y divide-border">
                                    {openAlerts.map((alert) => (
                                        <div key={alert.key} className="flex items-center justify-between px-6 py-3">
                                            <div>
                                                <p className="text-sm font-medium text-foreground">{alert.code}</p>
                                                <p className="text-xs text-muted-foreground">{alert.meaning}</p>
                                            </div>
                                            <span className="text-xs text-muted-foreground">{formatDateTime(alert.logTime)}</span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            ) : null} */}
            {tab === 'Overview' ? (
                <div className="grid gap-4 lg:grid-cols-3">
                    {/* Status / Ignition */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Operational Status</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center gap-2">
                                <span className={`h-3 w-3 rounded-full ${vehicle.liveState?.ignitionOn ? 'bg-emerald-500' : 'bg-zinc-400'}`} />
                                <p className="text-2xl font-semibold text-foreground">
                                    {vehicle.liveState?.ignitionOn ? 'Engine Running' : 'Engine Off'}
                                </p>
                            </div>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {vehicle.liveState?.isMoving ? 'In Motion' : 'Stationary'}
                            </p>
                        </CardContent>
                    </Card>

                    {/* Battery Health */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Battery Health</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.batteryVoltage ?? '—'}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">Vehicle electrical system</p>
                        </CardContent>
                    </Card>

                    {/* Total Operating Engine Hours */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Engine Hours</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.engineHoursFormatted ?? '—'}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">Cumulative engine runtime</p>
                        </CardContent>
                    </Card>

                    {/* Fuel Level (Show only if supported) */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Fuel Level</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.fuelLevel !== null && vehicle.liveState?.fuelLevel !== undefined && vehicle.liveState.fuelLevel > 0
                                    ? `${Math.round(vehicle.liveState.fuelLevel)}%`
                                    : 'Calibrating / Sensor N/A'}
                            </p>
                        </CardContent>
                    </Card>

                    {/* Last Communication */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Last Recorded Ping</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-lg font-medium text-foreground">
                                {formatDateTime(vehicle.liveState?.reportedAt ?? null)}
                            </p>
                        </CardContent>
                    </Card>

                    {/* Open Alerts / Diagnostics */}
                    <Card className="lg:col-span-3">
                        <CardHeader>
                            <CardTitle>Active Diagnostic & Behavior Alerts</CardTitle>
                        </CardHeader>
                        <CardContent className="px-0">
                            {openAlerts.length === 0 ? (
                                <p className="px-6 text-sm text-muted-foreground">No active faults or behavioral alerts.</p>
                            ) : (
                                <div className="divide-y divide-border">
                                    {openAlerts.map((alert) => (
                                        <div key={alert.key} className="flex items-center justify-between px-6 py-3">
                                            <div>
                                                <p className="text-sm font-medium text-foreground">{alert.code}</p>
                                                <p className="text-xs text-muted-foreground">{alert.meaning}</p>
                                            </div>
                                            <span className="text-xs text-muted-foreground">{formatDateTime(alert.logTime)}</span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            ) : null}

            {tab === 'Trip History' ? (
                <Card className="overflow-hidden py-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-6 py-3 font-medium">Start</th>
                                    <th className="px-6 py-3 font-medium">Route</th>
                                    <th className="px-6 py-3 font-medium">Distance</th>
                                    <th className="px-6 py-3 font-medium">Avg Speed</th>
                                    <th className="px-6 py-3 font-medium">Max Speed</th>
                                    <th className="px-6 py-3 font-medium">Fuel Used</th>
                                    <th className="px-6 py-3 font-medium"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {vehicle.trips.map((trip) => (
                                    <tr key={trip.id}>
                                        <td className="px-6 py-3 text-foreground">{formatDateTime(trip.startTime)}</td>
                                        <td className="max-w-xs truncate px-6 py-3 text-muted-foreground">
                                            {trip.startAddress || trip.endAddress ? `${trip.startAddress ?? '—'} → ${trip.endAddress ?? '—'}` : '—'}
                                        </td>
                                        <td className="px-6 py-3 text-muted-foreground">
                                            {trip.distanceKm !== null ? `${trip.distanceKm.toFixed(1)} km` : '—'}
                                        </td>
                                        <td className="px-6 py-3 text-muted-foreground">
                                            {trip.averageSpeed !== null ? `${Math.round(trip.averageSpeed)} km/h` : '—'}
                                        </td>
                                        <td className="px-6 py-3 text-muted-foreground">
                                            {trip.maxSpeed !== null ? `${Math.round(trip.maxSpeed)} km/h` : '—'}
                                        </td>
                                        <td className="px-6 py-3 text-muted-foreground">
                                            {trip.fuelConsumed !== null ? `${trip.fuelConsumed.toFixed(1)} L` : '—'}
                                        </td>
                                        <td className="px-6 py-3 text-right">
                                            <Button size="sm" variant="outline" onClick={() => setSelectedTrip(trip)}>
                                                Details
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                                {vehicle.trips.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-6 py-10 text-center text-muted-foreground">
                                            No trips recorded yet.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </Card>
            ) : null}

            <Dialog open={!!selectedTrip} onOpenChange={(open) => !open && setSelectedTrip(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Trip details</DialogTitle>
                    </DialogHeader>
                    {selectedTrip ? (
                        <dl className="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div>
                                <dt className="text-xs text-muted-foreground uppercase">Start time</dt>
                                <dd className="text-foreground">{formatDateTime(selectedTrip.startTime)}</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground uppercase">End time</dt>
                                <dd className="text-foreground">{formatDateTime(selectedTrip.endTime)}</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground uppercase">Start address</dt>
                                <dd className="text-foreground">{selectedTrip.startAddress ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground uppercase">End address</dt>
                                <dd className="text-foreground">{selectedTrip.endAddress ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground uppercase">Start coordinates</dt>
                                <dd className="text-foreground">
                                    {selectedTrip.startLatitude !== null && selectedTrip.startLongitude !== null
                                        ? `${selectedTrip.startLatitude.toFixed(5)}, ${selectedTrip.startLongitude.toFixed(5)}`
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground uppercase">End coordinates</dt>
                                <dd className="text-foreground">
                                    {selectedTrip.endLatitude !== null && selectedTrip.endLongitude !== null
                                        ? `${selectedTrip.endLatitude.toFixed(5)}, ${selectedTrip.endLongitude.toFixed(5)}`
                                        : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground uppercase">Start odometer</dt>
                                <dd className="text-foreground">
                                    {selectedTrip.startOdometer !== null ? `${selectedTrip.startOdometer.toLocaleString()} km` : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground uppercase">End odometer</dt>
                                <dd className="text-foreground">
                                    {selectedTrip.endOdometer !== null ? `${selectedTrip.endOdometer.toLocaleString()} km` : '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground uppercase">Driver</dt>
                                <dd className="text-foreground">{selectedTrip.driverName ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs text-muted-foreground uppercase">Driver ID</dt>
                                <dd className="text-foreground">{selectedTrip.driverUniqueId ?? '—'}</dd>
                            </div>
                        </dl>
                    ) : null}
                </DialogContent>
            </Dialog>

            {tab === 'Maintenance' ? (
                <Card className="overflow-hidden py-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-6 py-3 font-medium">Scheduled</th>
                                    <th className="px-6 py-3 font-medium">Completed</th>
                                    <th className="px-6 py-3 font-medium">Notes</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {vehicle.serviceEntries.map((entry) => (
                                    <tr key={entry.id}>
                                        <td className="px-6 py-3 text-foreground">{formatDateTime(entry.startsAt)}</td>
                                        <td className="px-6 py-3 text-muted-foreground">{formatDateTime(entry.endsAt)}</td>
                                        <td className="px-6 py-3 text-muted-foreground">{entry.comments ?? '—'}</td>
                                    </tr>
                                ))}
                                {vehicle.serviceEntries.length === 0 ? (
                                    <tr>
                                        <td colSpan={3} className="px-6 py-10 text-center text-muted-foreground">
                                            No service history yet.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </Card>
            ) : null}

            {tab === 'Documents' ? (
                <Card className="overflow-hidden py-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-6 py-3 font-medium">Document</th>
                                    <th className="px-6 py-3 font-medium">Expires</th>
                                    <th className="px-6 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {vehicle.documents.map((doc) => (
                                    <tr key={doc.id}>
                                        <td className="px-6 py-3 text-foreground">{doc.title ?? '—'}</td>
                                        <td className="px-6 py-3 text-muted-foreground">{formatDateTime(doc.expiresAt)}</td>
                                        <td className="px-6 py-3 text-muted-foreground capitalize">{doc.expiryStatus ?? '—'}</td>
                                    </tr>
                                ))}
                                {vehicle.documents.length === 0 ? (
                                    <tr>
                                        <td colSpan={3} className="px-6 py-10 text-center text-muted-foreground">
                                            No documents uploaded yet.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </Card>
            ) : null}

            {tab === 'Issues' ? (
                <Card className="overflow-hidden py-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-6 py-3 font-medium">Reported</th>
                                    <th className="px-6 py-3 font-medium">Summary</th>
                                    <th className="px-6 py-3 font-medium">Priority</th>
                                    <th className="px-6 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {vehicle.issues.map((issue) => (
                                    <tr key={issue.id}>
                                        <td className="px-6 py-3 text-foreground">{formatDateTime(issue.reportedAt)}</td>
                                        <td className="px-6 py-3 text-muted-foreground">{issue.summary}</td>
                                        <td className="px-6 py-3 text-muted-foreground capitalize">{issue.priority}</td>
                                        <td className="px-6 py-3 text-muted-foreground capitalize">{issue.status}</td>
                                    </tr>
                                ))}
                                {vehicle.issues.length === 0 ? (
                                    <tr>
                                        <td colSpan={4} className="px-6 py-10 text-center text-muted-foreground">
                                            No issues reported.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </Card>
            ) : null}
        </CompanyLayout>
    );
}
