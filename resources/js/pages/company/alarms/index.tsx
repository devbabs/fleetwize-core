import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { Pagination } from '@/components/company/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import CompanyLayout from '@/layouts/company/company-layout';
import type { Paginated } from '@/types/pagination';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type AlarmEvent = {
    id: number;
    vehicle: string;
    vehicleId: number;
    eventType: string;
    alarm: string | null;
    logTime: string | null;
    attributes: Record<string, unknown>;
};

type VehicleOption = {
    id: number;
    license_plate: string;
};

type Filters = {
    vehicle_id?: string | null;
    event_type?: string | null;
};

type Props = {
    events: PaginatedResponse<AlarmEvent>;
    vehicles: VehicleOption[];
    eventTypes: string[];
    filters: Filters;
};

//new type for alarm definitions

type AlarmDefinition = {
    label: string;
    meaning: string;
    trigger: string;
    severity: 'critical' | 'warning' | 'info';
};

// 1. Dictionary for Top-Level Event Types (event.eventType)
const EVENT_DEFINITIONS: Record<string, AlarmDefinition> = {
    ignitionOn: {
        label: 'Ignition On',
        meaning: 'Vehicle engine or accessory power turned on.',
        trigger: 'ACC ignition wire active, RPM > 0, or alternator voltage > 13.2V.',
        severity: 'info',
    },
    ignitionOff: {
        label: 'Ignition Off',
        meaning: 'Vehicle engine switched off.',
        trigger: 'ACC ignition wire off and engine RPM drops to 0.',
        severity: 'info',
    },
    deviceMoving: {
        label: 'Vehicle Moving',
        meaning: 'Vehicle started moving or resumed a trip.',
        trigger: 'GPS speed > 5 km/h or motion sensor activated.',
        severity: 'info',
    },
    deviceStopped: {
        label: 'Vehicle Stopped',
        meaning: 'Vehicle came to a complete halt.',
        trigger: 'Speed <= 5 km/h sustained for minimum stop duration.',
        severity: 'info',
    },
    deviceOverspeed: {
        label: 'Overspeeding',
        meaning: 'Vehicle exceeded the configured speed limit.',
        trigger: 'Reported speed > allowed speed limit threshold.',
        severity: 'critical',
    },
    geofenceEnter: {
        label: 'Geofence Enter',
        meaning: 'Vehicle entered a designated geofence zone.',
        trigger: 'GPS coordinates crossed inside geofence boundary.',
        severity: 'info',
    },
    geofenceExit: {
        label: 'Geofence Exit',
        meaning: 'Vehicle exited a designated geofence zone.',
        trigger: 'GPS coordinates crossed outside geofence boundary.',
        severity: 'warning',
    },
    maintenance: {
        label: 'Maintenance Due',
        meaning: 'Scheduled maintenance interval reached.',
        trigger: 'Remaining service distance <= 500 km or time interval elapsed.',
        severity: 'warning',
    },
    queuedCommandSent: {
        label: 'Command Sent',
        meaning: 'Queued remote command delivered to tracker.',
        trigger: 'Device came online and acknowledged pending command.',
        severity: 'info',
    },
    alarm: {
        label: 'Hardware Alarm',
        meaning: 'Tracker triggered a security or safety alarm.',
        trigger: 'Hardware sensor threshold breached on device.',
        severity: 'critical',
    },
};

// 2. Dictionary for Specific Hardware Alarms (event.alarm when eventType === 'alarm')
const ALARM_SUBTYPE_DEFINITIONS: Record<string, AlarmDefinition> = {
    vibration: {
        label: 'Vibration / Shock',
        meaning: 'Physical impact or vibration detected while parked.',
        trigger: 'Accelerometer G-sensor exceeded threshold while Ignition is OFF.',
        severity: 'warning',
    },
    movement: {
        label: 'Unauthorized Movement',
        meaning: 'Vehicle moved while ignition was switched off.',
        trigger: 'GPS position shifted outside parked radius with Ignition OFF.',
        severity: 'critical',
    },
    tow: {
        label: 'Towing Alert',
        meaning: 'Vehicle is being lifted or towed without ignition.',
        trigger: 'Tilt + GPS displacement detected while Ignition is OFF.',
        severity: 'critical',
    },
    powerOff: {
        label: 'Power Off',
        meaning: 'Tracker powered down completely.',
        trigger: 'Device switched off or internal backup battery depleted.',
        severity: 'critical',
    },
    powerOn: {
        label: 'Power On',
        meaning: 'Tracker booted up and connected.',
        trigger: 'Device power restored or cold boot completed.',
        severity: 'info',
    },
    powerCut: {
        label: 'Main Power Cut / Unplugged',
        meaning: 'External vehicle power lost (OBD unplugged or wire cut).',
        trigger: 'Vehicle supply voltage dropped to 0V; running on backup battery.',
        severity: 'critical',
    },
    powerRestored: {
        label: 'Main Power Restored',
        meaning: 'External vehicle power reconnected.',
        trigger: '12V/24V supply voltage restored to tracker.',
        severity: 'info',
    },
    lowBattery: {
        label: 'Low Tracker Battery',
        meaning: 'Tracker internal backup battery is critically low.',
        trigger: 'Backup battery level dropped below 20%.',
        severity: 'warning',
    },
    lowPower: {
        label: 'Low Vehicle Battery',
        meaning: 'Vehicle starter battery voltage is dangerously low.',
        trigger: 'Vehicle battery voltage dropped below 11.5V.',
        severity: 'warning',
    },
    sos: {
        label: 'SOS Panic Alert',
        meaning: 'Driver triggered the emergency SOS alert.',
        trigger: 'In-cabin SOS panic button pressed.',
        severity: 'critical',
    },
    overspeed: {
        label: 'Overspeed Alarm',
        meaning: 'Vehicle exceeded hardware speed threshold.',
        trigger: 'Speed > configured hardware limit.',
        severity: 'critical',
    },
    hardAcceleration: {
        label: 'Harsh Acceleration',
        meaning: 'Aggressive throttle acceleration detected.',
        trigger: 'Forward G-force exceeded driver behavior threshold.',
        severity: 'warning',
    },
    hardBraking: {
        label: 'Harsh Braking',
        meaning: 'Sudden or emergency braking detected.',
        trigger: 'Deceleration G-force exceeded driver behavior threshold.',
        severity: 'warning',
    },
    hardCornering: {
        label: 'Harsh Cornering',
        meaning: 'Sharp cornering at high speed.',
        trigger: 'Lateral G-force exceeded cornering threshold.',
        severity: 'warning',
    },
    accident: {
        label: 'Crash / Impact',
        meaning: 'Potential collision impact detected.',
        trigger: 'High-G impact spike detected by accelerometer.',
        severity: 'critical',
    },
    removing: {
        label: 'Device Removed',
        meaning: 'OBD tracker was unplugged from the diagnostic port.',
        trigger: 'Physical disconnect switch triggered.',
        severity: 'critical',
    },
    tampering: {
        label: 'Device Tampering',
        meaning: 'Physical tampering with tracker hardware.',
        trigger: 'Casing or tamper sensor triggered.',
        severity: 'critical',
    },
    jamming: {
        label: 'Signal Jamming',
        meaning: 'GPS/GSM signal interference detected.',
        trigger: 'RF jamming detected by cellular modem.',
        severity: 'critical',
    },
};

// 3. Helper to resolve any event row into its full definition
function resolveEventDefinition(event: AlarmEvent): AlarmDefinition {
    if (event.alarm && ALARM_SUBTYPE_DEFINITIONS[event.alarm]) {
        return ALARM_SUBTYPE_DEFINITIONS[event.alarm];
    }

    if (EVENT_DEFINITIONS[event.eventType]) {
        return EVENT_DEFINITIONS[event.eventType];
    }

    return {
        label: event.alarm ?? event.eventType,
        meaning: event.attributes?.message ? String(event.attributes.message) : 'System telematics event logged.',
        trigger: 'Reported by vehicle telematics unit.',
        severity: event.eventType === 'alarm' ? 'critical' : 'info',
    };
}

function severityBadge(severity: AlarmDefinition['severity'], label: string) {
    if (severity === 'critical') {
        return <Badge variant="destructive">{label}</Badge>;
    }
    if (severity === 'warning') {
        return <Badge variant="secondary">{label}</Badge>;
    }
    return <Badge variant="outline">{label}</Badge>;
}

//end of new type for alarm definitions

const eventTypeLabels: Record<string, string> = {
    ignitionOn: 'Ignition On',
    ignitionOff: 'Ignition Off',
    deviceMoving: 'Vehicle Moving',
    deviceStopped: 'Vehicle Stopped',
    alarm: 'Alarm',
    geofenceEnter: 'Geofence Enter',
    geofenceExit: 'Geofence Exit',
    maintenance: 'Maintenance',
    queuedCommandSent: 'Command Queued',
};

function formatDateTime(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function eventTypeBadge(eventType: string) {
    switch (eventType) {
        case 'alarm':
            return <Badge variant="destructive">Alarm</Badge>;

        case 'maintenance':
            return <Badge variant="secondary">Maintenance</Badge>;

        case 'geofenceEnter':
        case 'geofenceExit':
            return <Badge variant="outline">Geofence</Badge>;

        default:
            return <Badge variant="outline">{eventTypeLabels[eventType] ?? eventType}</Badge>;
    }
}

function AcknowledgeButton({ eventId }: { eventId: number }) {
    const [loading, setLoading] = useState(false);

    return (
        <Button
            size="sm"
            variant="outline"
            loading={loading}
            onClick={() => {
                setLoading(true);

                router.patch(
                    `/alarms/${eventId}/clear`,
                    {},
                    {
                        preserveScroll: true,
                        onFinish: () => setLoading(false),
                    },
                );
            }}
        >
            Acknowledge
        </Button>
    );
}

export default function AlarmsIndex({
    events,
    vehicles,
    eventTypes,
    filters,
}: Props) {
    console.log('vehicles:', vehicles);
    console.log('eventTypes:', eventTypes);
    console.log('filters:', filters);

    return (
        <CompanyLayout title="Alarms & Alerts">
            <Head title="Alarms & Alerts" />

            <div className="mb-4 flex items-center gap-4">
                <Select
                    value={filters.vehicle_id ?? 'all'}
                    onValueChange={(value) => {
                        router.get(
                            '/alarms',
                            {
                                vehicle_id:
                                    value === 'all' ? undefined : value,
                                event_type:
                                    filters.event_type || undefined,
                            },
                            {
                                preserveState: true,
                                replace: true,
                            },
                        );
                    }}
                >
                    <SelectTrigger className="w-[240px]">
                        <SelectValue placeholder="Filter by vehicle" />
                    </SelectTrigger>

                    <SelectContent className="z-[9999]">
                        <SelectItem value="all">
                            All Vehicles
                        </SelectItem>

                        {vehicles.map((vehicle) => (
                            <SelectItem
                                key={vehicle.id}
                                value={String(vehicle.id)}
                            >
                                {vehicle.license_plate}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Select
                    value={filters.event_type ?? 'all'}
                    onValueChange={(value) => {
                        router.get(
                            '/alarms',
                            {
                                vehicle_id:
                                    filters.vehicle_id || undefined,
                                event_type:
                                    value === 'all' ? undefined : value,
                            },
                            {
                                preserveState: true,
                                replace: true,
                            },
                        );
                    }}
                >
                    <SelectTrigger className="w-[240px]">
                        <SelectValue placeholder="Filter by event type" />
                    </SelectTrigger>

                    <SelectContent className="z-[9999]">
                        <SelectItem value="all">
                            All Event Types
                        </SelectItem>

                        {eventTypes.map((type) => (
                            <SelectItem
                                key={type}
                                value={type}
                            >
                                {eventTypeLabels[type] ?? type}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            {/* <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Event Type</th>
                                <th className="px-6 py-3 font-medium">Logged</th>
                                <th className="px-6 py-3 font-medium">Vehicle</th>
                                <th className="px-6 py-3 font-medium">Alarm</th>
                                <th className="px-6 py-3 font-medium">Details</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-border">
                            {events.data.map((event) => (
                                <tr key={event.id}>
                                    <td className="px-6 py-3">
                                        <Badge variant="outline">
                                            {event.eventType}
                                        </Badge>
                                    </td>

                                    <td className="px-6 py-3 text-muted-foreground">
                                        {formatDateTime(event.logTime)}
                                    </td>

                                    <td className="px-6 py-3 font-medium text-foreground">
                                        {event.vehicle}
                                    </td>

                                    <td className="px-6 py-3 text-muted-foreground">
                                        {event.alarm || '—'}
                                    </td>

                                    <td className="px-6 py-3 text-muted-foreground">
                                        {event.attributes?.message
                                            ? String(event.attributes.message)
                                            : '—'}
                                    </td>
                                </tr>
                            ))}

                            {events.data.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-6 py-10 text-center text-muted-foreground"
                                    >
                                        No events logged yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>

                <Pagination
                    links={events.links}
                    from={events.from}
                    to={events.to}
                    total={events.total}
                />
            </Card> */}
            
            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Event / Alarm</th>
                                <th className="px-6 py-3 font-medium">Logged</th>
                                <th className="px-6 py-3 font-medium">Vehicle</th>
                                <th className="px-6 py-3 font-medium">Alarm Sub-Type</th>
                                <th className="px-6 py-3 font-medium">Meaning & Trigger Condition</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-border">
                            {events.data.map((event) => {
                                const def = resolveEventDefinition(event);
                                const eventTypeLabel =
                                    EVENT_DEFINITIONS[event.eventType]?.label ?? event.eventType;

                                return (
                                    <tr key={event.id}>
                                        <td className="px-6 py-3">
                                            {severityBadge(def.severity, eventTypeLabel)}
                                        </td>

                                        <td className="px-6 py-3 whitespace-nowrap text-muted-foreground">
                                            {formatDateTime(event.logTime)}
                                        </td>

                                        <td className="px-6 py-3 font-medium text-foreground">
                                            {event.vehicle}
                                        </td>

                                        <td className="px-6 py-3 font-medium text-foreground">
                                            {event.alarm
                                                ? (ALARM_SUBTYPE_DEFINITIONS[event.alarm]?.label ?? event.alarm)
                                                : '—'}
                                        </td>

                                        <td className="px-6 py-3">
                                            <p className="font-medium text-foreground">
                                                {def.meaning}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Trigger: {def.trigger}
                                                {event.attributes?.message
                                                    ? ` (${String(event.attributes.message)})`
                                                    : ''}
                                            </p>
                                        </td>
                                    </tr>
                                );
                            })}

                            {events.data.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-6 py-10 text-center text-muted-foreground"
                                    >
                                        No events logged yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>

                <Pagination
                    links={events.links}
                    from={events.from}
                    to={events.to}
                    total={events.total}
                />
            </Card>

        </CompanyLayout>
    );
}
