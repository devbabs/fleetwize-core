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
                            route('company.alarms.index'),
                            {
                                vehicle_id:
                                    value === 'all' ? undefined : value,
                                event_type:
                                    filters.event_type || undefined,
                            },
                            {
                                preserveState: true,
                                replace: true,
                            }
                        );
                    }}
                >
                    <SelectTrigger className="w-[240px]">
                        <SelectValue placeholder="Filter by vehicle" />
                    </SelectTrigger>

                    <SelectContent>
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
                            route('company.alarms.index'),
                            {
                                vehicle_id:
                                    filters.vehicle_id || undefined,
                                event_type:
                                    value === 'all' ? undefined : value,
                            },
                            {
                                preserveState: true,
                                replace: true,
                            }
                        );
                    }}
                >
                    <SelectTrigger className="w-[240px]">
                        <SelectValue placeholder="Filter by event type" />
                    </SelectTrigger>

                    <SelectContent>
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

            <Card className="overflow-hidden py-0">
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
            </Card>
        </CompanyLayout>
    );
}
