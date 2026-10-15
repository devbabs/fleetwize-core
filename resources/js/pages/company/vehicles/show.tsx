import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus } from 'lucide-react';
import { useState } from 'react';
// import { route } from 'ziggy-js';

import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useVehicleLiveUpdates } from '@/hooks/use-vehicle-live-updates';
import CompanyLayout from '@/layouts/company/company-layout';
import { cn } from '@/lib/utils';

import {Select, SelectContent, SelectItem, SelectTrigger, SelectValue,} from '@/components/ui/select';

type Trip = {
    id: number;
    startTime: string | null;
    endTime: string | null;
    distanceKm: number | null;
    durationSeconds: number | null;
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

type VehicleEvent = {
    id: number;
    eventType: string;
    alarm: string | null;
    logTime: string | null;
    attributes: Record<string, unknown>;
};

type VehicleDocument = {
    id: number;
    documentId: number;
    name: string | null;
    documentNumber: string | null;
    lastRenewedAt: string | null;
    expiresAt: string | null;
    status: string | null;
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
        uniqueId: string | null;
        deviceStatus: string | null;
        protocol: string | null;
        altitude: number | null;
        gpsValid: boolean | null;
        deviceTime: string | null;
        serverTime: string | null;
        odometer: number | null;
        obdOdometer: number | null;
        totalDistance: number | null;
        hardCorneringCount: number | null;
        hardAccelerationCount: number | null;
        hardDecelerationCount: number | null;
        status: string;
    } | null;
    trips: Trip[];
    faults: Fault[];
    // alarms: Fault[];
    events: VehicleEvent[];
    documents: VehicleDocument[];
    serviceEntries: ServiceEntry[];
    issues: Issue[];

    maintenanceSchedules: MaintenanceSchedule[];
    maintenanceRecords: MaintenanceRecord[];
    maintenanceAlerts: MaintenanceAlert[];
};

type MaintenanceSchedule = {
    id: number;
    name: string;
    distanceIntervalKm: number | null;
    timeIntervalDays: number | null;
    active: boolean;
};

type MaintenanceRecord = {
    id: number;
    maintainedAt: string | null;
    odometerKm: number;
    notes: string | null;
    maintenanceScheduleId: number | null;
};

type DocumentOption = {
    id: number;
    title: string;
};

type MaintenanceAlert = {
    id: number;
    maintenanceScheduleId: number;
    title: string;
    description: string | null;
    alertedAt: string | null;
    acknowledged: boolean;
};

type MaintenanceScheduleItem = {
    id: number;
    name: string;
    distanceIntervalKm: number | null;
    timeIntervalDays: number | null;
    baselineOdometerKm: number | null;
    lastServiceOdometerKm: number | null;
    lastMaintainedAt: string | null;
    distanceSinceServiceKm: number | null;
    remainingKm: number | null;
    daysRemaining: number | null;
    status: 'healthy' | 'due_soon' | 'overdue';
    active: boolean;
};

type MaintenanceRecordItem = {
    id: number;
    scheduleName: string | null;
    maintainedAt: string | null;
    odometerKm: number;
    notes: string | null;
    maintenanceScheduleId: number | null;
};

interface Props {
    companySlug: string;
    vehicle: VehicleDetail;
    documentOptions: DocumentOption[];
}

const tabs = ['Overview', 'Trip History', 'Alarms/Alerts', 'Maintenance', 'Documents', 'Issues'] as const;
type Tab = (typeof tabs)[number];

function formatDateTime(value: string | null) {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function resolveTrackerStatus(
    liveState: VehicleDetail['liveState'],
    fallbackStatus?: string
): 'moving' | 'online_idle' | 'idle' | 'offline' {
    const reportedAt = liveState?.reportedAt;

    if (!reportedAt) {
        return (fallbackStatus as any) ?? 'offline';
    }

    const diffMs = Date.now() - new Date(reportedAt).getTime();

    if (diffMs > 3600_000) {
        return 'offline';
    }

    if (diffMs > 300_000) {
        return 'idle';
    }

    if ((liveState?.speed ?? 0) > 5) {
        return 'moving';
    }

    return 'online_idle';
}

function severityBadge(severity: number | null) {
    if (severity !== null && severity >= 4) {
return <Badge variant="destructive">High</Badge>;
}

    if (severity !== null && severity >= 2) {
return <Badge variant="secondary">Medium</Badge>;
}

    return <Badge variant="outline">Low</Badge>;
}

function AcknowledgeButton({ faultId }: { faultId: number }) {
    const [loading, setLoading] = useState(false);

    return (
        <Button
            size="sm"
            variant="outline"
            loading={loading}
            onClick={() => {
                setLoading(true);
                router.patch(
                    `/alarms/${faultId}/clear`,
                    {},
                    { preserveScroll: true, onFinish: () => setLoading(false) },
                );
            }}
        >
            Acknowledge
        </Button>
    );
}

function getVehicleStatus(liveState: VehicleDetail['liveState'], isOnline: boolean): 'offline' | 'idle' | 'online_idle' | 'moving' {
    if (!liveState?.reportedAt) {
        return 'offline';
    }

    const reportedTime = new Date(liveState.reportedAt).getTime();
    const now = Date.now();
    const diffMinutes = (now - reportedTime) / 60_000;

    if (diffMinutes > 60) {
        return 'offline';
    }

    if (diffMinutes > 5) {
        return 'idle';
    }

    const speed = liveState.speed ?? 0;
    if (speed > 5) {
        return 'moving';
    }

    return 'online_idle';
}

function renderVehicleStatusBadge(status: string | undefined, isOnline: boolean) {
    switch (status) {
        case 'moving':
            return <Badge className="border-transparent bg-brand-green/15 text-brand-green">Moving</Badge>;
        case 'online_idle':
            return <Badge variant="secondary">Online - Idle</Badge>;
        case 'idle':
            return <Badge variant="secondary" className="bg-amber-500/15 text-amber-600">Idle</Badge>;
        case 'offline':
        default:
            return <Badge variant="outline">Offline</Badge>;
    }
}

const formatDuration = (seconds: number): string => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }

    if (minutes > 0) {
        return `${minutes}m ${remainingSeconds}s`;
    }

    return `${remainingSeconds}s`;
};

const eventTypeLabels: Record<string, string> = {
    ignitionOn: 'Ignition On',
    ignitionOff: 'Ignition Off',
    deviceMoving: 'Vehicle Moving',
    deviceStopped: 'Vehicle Stopped',
    alarm: 'Alarm',
    geofenceEnter: 'Geofence Enter',
    geofenceExit: 'Geofence Exit',
    maintenance: 'Maintenance',
};

export default function VehicleShow({ vehicle: initialVehicle, documentOptions = [], }: { vehicle: VehicleDetail; documentOptions?: DocumentOption[];}) {
    const [tab, setTab] = useState<Tab>('Overview');
    const [vehicle, setVehicle] = useState(initialVehicle);
    const [selectedTrip, setSelectedTrip] = useState<Trip | null>(null);
    const [documentDialogOpen, setDocumentDialogOpen] = useState(false);

    const {
        data: documentData,
        setData: setDocumentData,
        post: postDocument,
        processing: documentProcessing,
        errors: documentErrors,
        reset: resetDocument,
    } = useForm({
        document_id: '',
        document_number: '',
        last_renewed_at: '',
        expires_at: '',
    });

    const submitDocument = (e: React.FormEvent) => {
        e.preventDefault();

        postDocument(`/vehicles/${vehicle.id}/documents`, {
            preserveScroll: true,
            onSuccess: () => {
                setDocumentDialogOpen(false);
                resetDocument();
            },
        });
    };

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
                uniqueId: update.uniqueId,
                deviceStatus: update.deviceStatus,
                protocol: update.protocol,
                altitude: update.altitude,
                gpsValid: update.gpsValid,
                deviceTime: update.deviceTime,
                serverTime: update.serverTime,
                odometer: update.odometer,
                obdOdometer: update.obdOdometer,
                totalDistance: update.totalDistance,
                hardCorneringCount: update.hardCorneringCount,
                hardAccelerationCount: update.hardAccelerationCount,
                hardDecelerationCount: update.hardDecelerationCount,
                status: update.status,
            },
        }));
    });

    // Faults (manual OBD workshop scans) and alarms (live tracker events)
    // are separate underlying tables — their ids can collide, so tag each
    // with its source for a stable key before merging into one list of
    // "something's open for this vehicle".
    const openAlerts = [
        ...(vehicle.faults ?? []).map((fault) => ({
            key: `fault-${fault.id}`,
            type: 'fault',
            title: fault.meaning ?? fault.code ?? 'Fault',
            logTime: fault.logTime,
            clearedAt: fault.clearedAt,
            severity: fault.severity,
        })),

        ...(vehicle.events ?? []).map((event) => ({
            key: `event-${event.id}`,
            type: 'event',
            title:
                event.alarm ??
                eventTypeLabels[event.eventType] ??
                event.eventType,
            logTime: event.logTime,
            clearedAt: null,
            severity: null,
        })),
    ]
        .filter((alert) => !alert.clearedAt)
        .sort((a, b) => (b.logTime ?? '').localeCompare(a.logTime ?? ''));

    // Live odometer fallback: liveState.odometer -> vehicle.mileage -> 0
    const currentLiveOdometer = Math.round(
        Number(vehicle.liveState?.odometer ?? vehicle.mileage ?? 0)
    );

    const scheduleForm = useForm({
        name: 'Oil Change',
        distance_interval_km: '5000',
        time_interval_days: '180',
        baseline_odometer_km: String(currentLiveOdometer),
    });

    const applySchedulePreset = (name: string, distanceKm: number, days: number) => {
        scheduleForm.setData({
            name,
            distance_interval_km: String(distanceKm),
            time_interval_days: String(days),
            baseline_odometer_km: String(currentLiveOdometer),
        });
    };

    const handleCreateSchedule = (e: React.FormEvent) => {
        e.preventDefault();
        scheduleForm.post(`/vehicles/${vehicle.id}/maintenance-schedules`, {
            preserveScroll: true,
        });
    };

    const handleCompleteService = (schedule: MaintenanceScheduleItem) => {
        const confirmedOdo = window.prompt(
            `Confirm odometer reading (km) for completed ${schedule.name}:`,
            String(currentLiveOdometer)
        );

        if (confirmedOdo === null) return;

        router.post(
            `/vehicles/${vehicle.id}/maintenance-records`,
            {
                maintenance_schedule_id: schedule.id,
                maintained_at: new Date().toISOString().split('T')[0],
                odometer_km: Number(confirmedOdo) || currentLiveOdometer,
                notes: `Completed scheduled ${schedule.name}`,
            },
            { preserveScroll: true }
        );
    };

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
                {renderVehicleStatusBadge(
                    resolveTrackerStatus(vehicle.liveState, vehicle.liveState?.status),
                    vehicle.isOnline
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

            {tab === 'Overview' ? (
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Odometer</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.odometer !== null && vehicle.liveState?.odometer !== undefined
                                    ? `${vehicle.liveState.odometer.toLocaleString()} km`
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
                            <CardTitle className="text-sm text-muted-foreground">Total Distance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.totalDistance !== null && vehicle.liveState?.totalDistance !== undefined
                                    ? vehicle.liveState.totalDistance.toLocaleString()
                                    
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
                            <CardTitle className="text-sm text-muted-foreground">Engine RPM</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.engineRpm !== null && vehicle.liveState?.engineRpm !== undefined
                                    ? `${Math.round(vehicle.liveState.engineRpm)} RPM`
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
                            <CardTitle className="text-sm text-muted-foreground">Battery Voltage</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.batteryVoltage !== null && vehicle.liveState?.batteryVoltage !== undefined
                                    ? `${Math.round(vehicle.liveState.batteryVoltage)}V`
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
                                    ? `${(vehicle.liveState.engineHours / 3600).toFixed(1)} h`
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
                            <CardTitle className="text-sm text-muted-foreground">Hard Cornering</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.hardCorneringCount !== null && vehicle.liveState?.hardCorneringCount !== undefined
                                    ? vehicle.liveState.hardCorneringCount.toLocaleString()
                                    : '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Hard Acceleration</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.hardAccelerationCount !== null && vehicle.liveState?.hardAccelerationCount !== undefined
                                    ? vehicle.liveState.hardAccelerationCount.toLocaleString()
                                    : '—'}
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm text-muted-foreground">Hard Deceleration</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-2xl font-semibold text-foreground">
                                {vehicle.liveState?.hardDecelerationCount !== null && vehicle.liveState?.hardDecelerationCount !== undefined
                                    ? vehicle.liveState.hardDecelerationCount.toLocaleString()
                                    : '—'}
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
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <p className="text-sm font-medium text-foreground">
                                                            {alert.title}
                                                        </p>

                                                        {alert.type === 'fault' ? (
                                                            <Badge variant="destructive">Fault</Badge>
                                                        ) : (
                                                            <Badge variant="outline">Event</Badge>
                                                        )}
                                                    </div>
                                                </div>
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
                                    <th className="px-6 py-3 font-medium">Duration</th>
                                    <th className="px-6 py-3 font-medium">Distance</th>
                                    <th className="px-6 py-3 font-medium">Avg Speed</th>
                                    <th className="px-6 py-3 font-medium">Max Speed</th>
                                    {/* <th className="px-6 py-3 font-medium">Driver</th> */}
                                    <th className="px-6 py-3 font-medium">Playback</th>
                                    <th className="px-6 py-3 font-medium"></th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-border">
                                {vehicle.trips.map((trip) => (
                                    <tr key={trip.id}>
                                        <td className="px-6 py-3 text-foreground">
                                            {formatDateTime(trip.startTime)}
                                        </td>

                                        <td className="max-w-xs truncate px-6 py-3 text-muted-foreground">
                                            {trip.startAddress || trip.endAddress
                                                ? `${trip.startAddress ?? '—'} → ${trip.endAddress ?? '—'}`
                                                : '—'}
                                        </td>

                                        <td className="px-6 py-3 text-muted-foreground">
                                            {trip.durationSeconds !== null
                                                ? formatDuration(trip.durationSeconds)
                                                : '—'}
                                        </td>

                                        <td className="px-6 py-3 text-muted-foreground">
                                            {trip.distanceKm !== null
                                                ? `${trip.distanceKm.toFixed(1)} km`
                                                : '—'}
                                        </td>

                                        <td className="px-6 py-3 text-muted-foreground">
                                            {trip.averageSpeed !== null
                                                ? `${Math.round(trip.averageSpeed)} km/h`
                                                : '—'}
                                        </td>

                                        <td className="px-6 py-3 text-muted-foreground">
                                            {trip.maxSpeed !== null
                                                ? `${Math.round(trip.maxSpeed)} km/h`
                                                : '—'}
                                        </td>

                                        {/* <td className="px-6 py-3 text-muted-foreground">
                                            {trip.driverName ?? '—'}
                                        </td> */}

                                        <td>
                                            <Button asChild size="sm">
                                                <Link
                                                    href={`/vehicles/${vehicle.id}/trips/${trip.id}/playback`}
                                                >
                                                    Playback
                                                </Link>
                                            </Button>
                                        </td>

                                        <td className="px-6 py-3 text-right">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() => setSelectedTrip(trip)}
                                            >
                                                Details
                                            </Button>
                                        </td>
                                    </tr>
                                ))}

                                {vehicle.trips.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={7}
                                            className="px-6 py-10 text-center text-muted-foreground"
                                        >
                                            No trips recorded yet.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </Card>
            ) : null}

            {tab === 'Alarms/Alerts' ? (
                <Card className="overflow-hidden py-0">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                <tr>
                                    <th className="px-6 py-3 font-medium">
                                        Event
                                    </th>

                                    <th className="px-6 py-3 font-medium">
                                        Logged
                                    </th>

                                    <th className="px-6 py-3 font-medium">
                                        Details
                                    </th>

                                    <th className="px-6 py-3 font-medium">
                                        Status
                                    </th>
                                </tr>
                            </thead>

                            <tbody className="divide-y divide-border">
                                {vehicle.events.map((event) => (
                                    <tr key={event.id}>
                                        <td className="px-6 py-3">
                                            <span className="font-medium text-foreground">
                                                {eventTypeLabels[event.eventType] ??
                                                    event.eventType}
                                            </span>
                                        </td>

                                        <td className="px-6 py-3 text-muted-foreground">
                                            {formatDateTime(event.logTime)}
                                        </td>

                                        <td className="px-6 py-3 text-muted-foreground">
                                            {event.alarm ??
                                                (event.attributes?.message
                                                    ? String(event.attributes.message)
                                                    : '—')}
                                        </td>

                                        <td className="px-6 py-3">
                                            {event.eventType === 'alarm' ? (
                                                <Badge className="border-transparent bg-brand-green/15 text-brand-green">
                                                    Alarm
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline">
                                                    Event
                                                </Badge>
                                            )}
                                        </td>
                                    </tr>
                                ))}

                                {vehicle.events.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={4}
                                            className="px-6 py-10 text-center text-muted-foreground"
                                        >
                                            No events logged yet.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </Card>
            ) : null}

            <Dialog
                open={!!selectedTrip}
                onOpenChange={(open) => !open && setSelectedTrip(null)}
            >
                <DialogContent className="max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Trip details</DialogTitle>
                    </DialogHeader>

                    {selectedTrip ? (
                        <div className="space-y-6">

                            {/* Trip Summary */}
                            <div>
                                <h3 className="mb-3 text-sm font-semibold text-foreground">
                                    Trip summary
                                </h3>

                                <dl className="grid grid-cols-2 gap-x-6 gap-y-4">
                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            Start time
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {formatDateTime(selectedTrip.startTime)}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            End time
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {formatDateTime(selectedTrip.endTime)}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            Duration
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {selectedTrip.durationSeconds !== null
                                                ? formatDuration(selectedTrip.durationSeconds)
                                                : '—'}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            Distance
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {selectedTrip.distanceKm !== null
                                                ? `${selectedTrip.distanceKm.toFixed(1)} km`
                                                : '—'}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            Average speed
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {selectedTrip.averageSpeed !== null
                                                ? `${Math.round(selectedTrip.averageSpeed)} km/h`
                                                : '—'}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            Maximum speed
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {selectedTrip.maxSpeed !== null &&
                                            selectedTrip.maxSpeed > 0
                                                ? `${Math.round(selectedTrip.maxSpeed)} km/h`
                                                : '—'}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            Fuel used
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {selectedTrip.fuelConsumed !== null
                                                ? `${selectedTrip.fuelConsumed.toFixed(1)} L`
                                                : '—'}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            {/* Route */}
                            <div>
                                <h3 className="mb-3 text-sm font-semibold text-foreground">
                                    Route
                                </h3>

                                <dl className="grid grid-cols-1 gap-4">
                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            Start address
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {selectedTrip.startAddress ?? '—'}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            End address
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {selectedTrip.endAddress ?? '—'}
                                        </dd>
                                    </div>

                                    <div className="grid grid-cols-2 gap-6">
                                        <div>
                                            <dt className="text-xs text-muted-foreground uppercase">
                                                Start coordinates
                                            </dt>
                                            <dd className="mt-1 text-sm text-foreground">
                                                {selectedTrip.startLatitude !== null &&
                                                selectedTrip.startLongitude !== null
                                                    ? `${selectedTrip.startLatitude.toFixed(5)}, ${selectedTrip.startLongitude.toFixed(5)}`
                                                    : '—'}
                                            </dd>
                                        </div>

                                        <div>
                                            <dt className="text-xs text-muted-foreground uppercase">
                                                End coordinates
                                            </dt>
                                            <dd className="mt-1 text-sm text-foreground">
                                                {selectedTrip.endLatitude !== null &&
                                                selectedTrip.endLongitude !== null
                                                    ? `${selectedTrip.endLatitude.toFixed(5)}, ${selectedTrip.endLongitude.toFixed(5)}`
                                                    : '—'}
                                            </dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>

                            {/* Vehicle / Driver */}
                            <div>
                                <h3 className="mb-3 text-sm font-semibold text-foreground">
                                    Vehicle & driver
                                </h3>

                                <dl className="grid grid-cols-2 gap-x-6 gap-y-4">
                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            Start odometer
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {selectedTrip.startOdometer !== null
                                                ? `${selectedTrip.startOdometer.toLocaleString()} km`
                                                : '—'}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            End odometer
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {selectedTrip.endOdometer !== null
                                                ? `${selectedTrip.endOdometer.toLocaleString()} km`
                                                : '—'}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            Driver
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {selectedTrip.driverName ?? '—'}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="text-xs text-muted-foreground uppercase">
                                            Driver ID
                                        </dt>
                                        <dd className="mt-1 text-sm text-foreground">
                                            {selectedTrip.driverUniqueId ?? '—'}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                        </div>
                    ) : null}
                </DialogContent>
            </Dialog>


            {/* {tab === 'Maintenance' ? (
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
            ) : null} */}

            {/* {tab === 'Maintenance' ? (
                <div className="space-y-6">

                    {vehicle.maintenanceAlerts.length > 0 ? (
                        <Card className="border-destructive/30">
                            <CardHeader>
                                <CardTitle>Maintenance Alerts</CardTitle>
                            </CardHeader>

                            <CardContent className="space-y-3">
                                {vehicle.maintenanceAlerts.map((alert) => (
                                    <div
                                        key={alert.id}
                                        className="rounded-lg border border-destructive/30 bg-destructive/5 p-4"
                                    >
                                        <p className="font-medium text-foreground">
                                            {alert.title}
                                        </p>

                                        {alert.description ? (
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {alert.description}
                                            </p>
                                        ) : null}

                                        {alert.alertedAt ? (
                                            <p className="mt-2 text-xs text-muted-foreground">
                                                Alerted {formatDateTime(alert.alertedAt)}
                                            </p>
                                        ) : null}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    ) : null}

                    <Card className="overflow-hidden py-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-6 py-3 font-medium">
                                            Maintenance
                                        </th>
                                        <th className="px-6 py-3 font-medium">
                                            Distance Interval
                                        </th>
                                        <th className="px-6 py-3 font-medium">
                                            Time Interval
                                        </th>
                                        <th className="px-6 py-3 font-medium">
                                            Status
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-border">
                                    {vehicle.maintenanceSchedules.map((schedule) => (
                                        <tr key={schedule.id}>
                                            <td className="px-6 py-3 font-medium text-foreground">
                                                {schedule.name}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {schedule.distanceIntervalKm !== null
                                                    ? `${schedule.distanceIntervalKm.toLocaleString()} km`
                                                    : '—'}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {schedule.timeIntervalDays !== null
                                                    ? `${schedule.timeIntervalDays} days`
                                                    : '—'}
                                            </td>

                                            <td className="px-6 py-3">
                                                <Badge variant="outline">
                                                    Active
                                                </Badge>
                                            </td>
                                        </tr>
                                    ))}

                                    {vehicle.maintenanceSchedules.length === 0 ? (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="px-6 py-10 text-center text-muted-foreground"
                                            >
                                                No maintenance schedules configured.
                                            </td>
                                        </tr>
                                    ) : null}
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <Card className="overflow-hidden py-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-6 py-3 font-medium">
                                            Scheduled
                                        </th>
                                        <th className="px-6 py-3 font-medium">
                                            Completed
                                        </th>
                                        <th className="px-6 py-3 font-medium">
                                            Notes
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-border">
                                    {vehicle.serviceEntries.map((entry) => (
                                        <tr key={entry.id}>
                                            <td className="px-6 py-3 text-foreground">
                                                {formatDateTime(entry.startsAt)}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {formatDateTime(entry.endsAt)}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {entry.comments ?? '—'}
                                            </td>
                                        </tr>
                                    ))}

                                    {vehicle.serviceEntries.length === 0 ? (
                                        <tr>
                                            <td
                                                colSpan={3}
                                                className="px-6 py-10 text-center text-muted-foreground"
                                            >
                                                No service history yet.
                                            </td>
                                        </tr>
                                    ) : null}
                                </tbody>
                            </table>
                        </div>
                    </Card>

                </div>
            ) : null} */}

            {tab === 'Maintenance' ? (
                <div className="space-y-6">
                    {/* 1. Active Maintenance Alerts */}
                    {vehicle.maintenanceAlerts.length > 0 ? (
                        <Card className="border-destructive/30">
                            <CardHeader>
                                <CardTitle>Maintenance Alerts</CardTitle>
                            </CardHeader>

                            <CardContent className="space-y-3">
                                {vehicle.maintenanceAlerts.map((alert) => (
                                    <div
                                        key={alert.id}
                                        className="rounded-lg border border-destructive/30 bg-destructive/5 p-4"
                                    >
                                        <p className="font-medium text-foreground">
                                            {alert.title}
                                        </p>

                                        {alert.description ? (
                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {alert.description}
                                            </p>
                                        ) : null}

                                        {alert.alertedAt ? (
                                            <p className="mt-2 text-xs text-muted-foreground">
                                                Alerted {formatDateTime(alert.alertedAt)}
                                            </p>
                                        ) : null}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    ) : null}

                    {/* 2. Configure Per-Vehicle Maintenance Schedule & Baseline Odometer */}
                    <Card>
                        <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <CardTitle className="text-base">
                                    Add Maintenance Schedule
                                </CardTitle>
                                <p className="text-sm text-muted-foreground">
                                    Current Vehicle Odometer:{' '}
                                    <span className="font-semibold text-foreground">
                                        {currentLiveOdometer.toLocaleString()} km
                                    </span>
                                    . Set the baseline odometer from when this service was last done.
                                </p>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => applySchedulePreset('Oil Change', 5000, 180)}
                                >
                                    Preset: Oil Change (5,000 km)
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => applySchedulePreset('Tire Rotation', 10000, 365)}
                                >
                                    Preset: Tire Rotation (10,000 km)
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => applySchedulePreset('General Service', 20000, 365)}
                                >
                                    Preset: General Service (20,000 km)
                                </Button>
                            </div>
                        </CardHeader>

                        <CardContent>
                            <form
                                onSubmit={handleCreateSchedule}
                                className="grid grid-cols-1 gap-4 sm:grid-cols-5"
                            >
                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">
                                        Maintenance Name
                                    </label>
                                    <input
                                        type="text"
                                        className="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        value={scheduleForm.data.name}
                                        onChange={(e) => scheduleForm.setData('name', e.target.value)}
                                        placeholder="e.g. Oil Change"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">
                                        Distance Interval (km)
                                    </label>
                                    <input
                                        type="number"
                                        className="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        value={scheduleForm.data.distance_interval_km}
                                        onChange={(e) =>
                                            scheduleForm.setData('distance_interval_km', e.target.value)
                                        }
                                        min={100}
                                        placeholder="5000"
                                    />
                                </div>

                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">
                                        Time Interval (Days)
                                    </label>
                                    <input
                                        type="number"
                                        className="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        value={scheduleForm.data.time_interval_days}
                                        onChange={(e) =>
                                            scheduleForm.setData('time_interval_days', e.target.value)
                                        }
                                        min={1}
                                        placeholder="180"
                                    />
                                </div>

                                <div>
                                    <label className="text-xs font-medium text-muted-foreground">
                                        Baseline Odometer (km)
                                    </label>
                                    <input
                                        type="number"
                                        className="mt-1 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        value={scheduleForm.data.baseline_odometer_km}
                                        onChange={(e) =>
                                            scheduleForm.setData('baseline_odometer_km', e.target.value)
                                        }
                                        min={0}
                                        required
                                    />
                                </div>

                                <div className="flex items-end">
                                    <Button
                                        type="submit"
                                        disabled={scheduleForm.processing}
                                        className="w-full"
                                    >
                                        Save Schedule
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* 3. Maintenance Schedules Table */}
                    <Card className="overflow-hidden py-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-6 py-3 font-medium">Maintenance</th>
                                        <th className="px-6 py-3 font-medium">Interval</th>
                                        <th className="px-6 py-3 font-medium">Last Service Odo</th>
                                        <th className="px-6 py-3 font-medium">Distance Since Service</th>
                                        <th className="px-6 py-3 font-medium">Remaining Distance</th>
                                        <th className="px-6 py-3 font-medium">Status</th>
                                        <th className="px-6 py-3 font-medium text-right">Action</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-border">
                                    {vehicle.maintenanceSchedules.map((schedule) => (
                                        <tr key={schedule.id}>
                                            <td className="px-6 py-3 font-medium text-foreground">
                                                {schedule.name}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {schedule.distanceIntervalKm !== null
                                                    ? `${schedule.distanceIntervalKm.toLocaleString()} km`
                                                    : '—'}
                                                {schedule.timeIntervalDays !== null
                                                    ? ` / ${schedule.timeIntervalDays} days`
                                                    : ''}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {schedule.lastServiceOdometerKm !== null
                                                    ? `${schedule.lastServiceOdometerKm.toLocaleString()} km`
                                                    : '—'}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {schedule.distanceSinceServiceKm !== null
                                                    ? `${schedule.distanceSinceServiceKm.toLocaleString()} km`
                                                    : '—'}
                                            </td>

                                            <td className="px-6 py-3 font-medium text-foreground">
                                                {schedule.remainingKm !== null
                                                    ? `${schedule.remainingKm.toLocaleString()} km`
                                                    : '—'}
                                            </td>

                                            <td className="px-6 py-3">
                                                <Badge
                                                    variant={
                                                        schedule.status === 'overdue'
                                                            ? 'destructive'
                                                            : schedule.status === 'due_soon'
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {schedule.status === 'overdue'
                                                        ? 'Overdue'
                                                        : schedule.status === 'due_soon'
                                                        ? 'Due Soon (≤ 500 km)'
                                                        : 'Healthy'}
                                                </Badge>
                                            </td>

                                            <td className="px-6 py-3 text-right">
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => handleCompleteService(schedule)}
                                                >
                                                    Log Service
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}

                                    {vehicle.maintenanceSchedules.length === 0 ? (
                                        <tr>
                                            <td
                                                colSpan={7}
                                                className="px-6 py-10 text-center text-muted-foreground"
                                            >
                                                No maintenance schedules configured for this vehicle yet. Use the form above to add one.
                                            </td>
                                        </tr>
                                    ) : null}
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    {/* 4. Completed Maintenance History (Using maintenanceRecords) */}
                    <Card className="overflow-hidden py-0">
                        <div className="border-b px-6 py-4">
                            <h3 className="font-medium">Service History</h3>
                            <p className="text-sm text-muted-foreground">
                                Completed maintenance logs and odometer baselines.
                            </p>
                        </div>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-6 py-3 font-medium">Service</th>
                                        <th className="px-6 py-3 font-medium">Completed Date</th>
                                        <th className="px-6 py-3 font-medium">Odometer at Service</th>
                                        <th className="px-6 py-3 font-medium">Notes</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-border">
                                    {(vehicle.maintenanceRecords ?? []).map((record) => (
                                        <tr key={record.id}>
                                            <td className="px-6 py-3 font-medium text-foreground">
                                                {record.scheduleName ?? 'General Service'}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {record.maintainedAt ?? '—'}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {record.odometerKm !== null
                                                    ? `${Number(record.odometerKm).toLocaleString()} km`
                                                    : '—'}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {record.notes ?? '—'}
                                            </td>
                                        </tr>
                                    ))}

                                    {(!vehicle.maintenanceRecords ||
                                        vehicle.maintenanceRecords.length === 0) ? (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="px-6 py-10 text-center text-muted-foreground"
                                            >
                                                No service history logged yet.
                                            </td>
                                        </tr>
                                    ) : null}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>
            ) : null}

            {tab === 'Documents' ? (
                <>
                    <Card className="overflow-hidden py-0">
                        <div className="flex items-center justify-between border-b px-6 py-4">
                            <div>
                                <h3 className="font-medium">Vehicle documents</h3>
                                <p className="text-sm text-muted-foreground">
                                    Documents and compliance records for this vehicle.
                                </p>
                            </div>

                            <Button
                                onClick={() => {
                                    resetDocument();
                                    setDocumentDialogOpen(true);
                                }}
                                className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy"
                            >
                                <Plus className="size-4" />
                                Add document
                            </Button>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                                    <tr>
                                        <th className="px-6 py-3 font-medium">Document</th>
                                        <th className="px-6 py-3 font-medium">Document ID</th>
                                        <th className="px-6 py-3 font-medium">Expires</th>
                                        <th className="px-6 py-3 font-medium">Status</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-border">
                                    {vehicle.documents.map((doc) => (
                                        <tr key={doc.id}>
                                            <td className="px-6 py-3 font-medium text-foreground">
                                                {doc.name ?? '—'}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {doc.documentNumber ?? '—'}
                                            </td>

                                            <td className="px-6 py-3 text-muted-foreground">
                                                {doc.expiresAt
                                                    ? formatDateTime(doc.expiresAt)
                                                    : '—'}
                                            </td>

                                            <td className="px-6 py-3 capitalize">
                                                {doc.status ?? '—'}
                                            </td>
                                        </tr>
                                    ))}

                                    {vehicle.documents.length === 0 ? (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="px-6 py-10 text-center text-muted-foreground"
                                            >
                                                No documents added yet.
                                            </td>
                                        </tr>
                                    ) : null}
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <Dialog open={documentDialogOpen} onOpenChange={setDocumentDialogOpen}>
                        <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                            <DialogHeader>
                                <DialogTitle>Add vehicle document</DialogTitle>
                            </DialogHeader>

                            <form onSubmit={submitDocument} className="space-y-4">
                                {/* Document Type */}
                                <div className="grid gap-2">
                                    <Label htmlFor="document_id">Document type</Label>

                                    <Select
                                        value={documentData.document_id ? String(documentData.document_id) : ''}
                                        onValueChange={(value) =>
                                            setDocumentData('document_id', Number(value))
                                        }
                                    >
                                        <SelectTrigger id="document_id" className="w-full">
                                            <SelectValue placeholder="Select document type" />
                                        </SelectTrigger>

                                        <SelectContent className="z-[9999]">
                                            {(documentOptions ?? [])
                                                .filter((document) => document.title !== 'Driver License')
                                                .map((document) => (
                                                    <SelectItem
                                                        key={document.id}
                                                        value={String(document.id)}
                                                    >
                                                        {document.title}
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>

                                    {documentErrors.document_id ? (
                                        <p className="text-xs text-destructive">
                                            {documentErrors.document_id}
                                        </p>
                                    ) : null}
                                </div>

                                {/* Document Number / ID */}
                                <div className="grid gap-2">
                                    <Label htmlFor="document_number">
                                        Document ID / Number
                                    </Label>

                                    <Input
                                        id="document_number"
                                        placeholder="e.g. INS-2026-001234"
                                        value={documentData.document_number}
                                        onChange={(e) =>
                                            setDocumentData('document_number', e.target.value)
                                        }
                                    />

                                    {documentErrors.document_number ? (
                                        <p className="text-xs text-destructive">
                                            {documentErrors.document_number}
                                        </p>
                                    ) : null}
                                </div>

                                {/* Dates */}
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="last_renewed_at">
                                            Last renewed
                                        </Label>

                                        <Input
                                            id="last_renewed_at"
                                            type="date"
                                            value={documentData.last_renewed_at}
                                            onChange={(e) =>
                                                setDocumentData(
                                                    'last_renewed_at',
                                                    e.target.value,
                                                )
                                            }
                                        />

                                        {documentErrors.last_renewed_at ? (
                                            <p className="text-xs text-destructive">
                                                {documentErrors.last_renewed_at}
                                            </p>
                                        ) : null}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="expires_at">
                                            Expiry date
                                        </Label>

                                        <Input
                                            id="expires_at"
                                            type="date"
                                            value={documentData.expires_at}
                                            onChange={(e) =>
                                                setDocumentData('expires_at', e.target.value)
                                            }
                                        />

                                        {documentErrors.expires_at ? (
                                            <p className="text-xs text-destructive">
                                                {documentErrors.expires_at}
                                            </p>
                                        ) : null}
                                    </div>
                                </div>

                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setDocumentDialogOpen(false)}
                                    >
                                        Cancel
                                    </Button>

                                    <Button
                                        type="submit"
                                        loading={documentProcessing}
                                        className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy"
                                    >
                                        Save document
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </>

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
