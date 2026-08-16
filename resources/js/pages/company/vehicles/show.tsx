import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
    } | null;
    trips: Trip[];
    faults: Fault[];
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

export default function VehicleShow({ vehicle }: { vehicle: VehicleDetail }) {
    const [tab, setTab] = useState<Tab>('Overview');

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

            {tab === 'Overview' ? (
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

                    <Card className="lg:col-span-3">
                        <CardHeader>
                            <CardTitle>Open Alerts</CardTitle>
                        </CardHeader>
                        <CardContent className="px-0">
                            {vehicle.faults.filter((f) => !f.clearedAt).length === 0 ? (
                                <p className="px-6 text-sm text-muted-foreground">No open alerts.</p>
                            ) : (
                                <div className="divide-y divide-border">
                                    {vehicle.faults
                                        .filter((f) => !f.clearedAt)
                                        .map((fault) => (
                                            <div key={fault.id} className="flex items-center justify-between px-6 py-3">
                                                <div>
                                                    <p className="text-sm font-medium text-foreground">{fault.code}</p>
                                                    <p className="text-xs text-muted-foreground">{fault.meaning}</p>
                                                </div>
                                                <span className="text-xs text-muted-foreground">{formatDateTime(fault.logTime)}</span>
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
                                    <th className="px-6 py-3 font-medium">Distance</th>
                                    <th className="px-6 py-3 font-medium">Avg Speed</th>
                                    <th className="px-6 py-3 font-medium">Max Speed</th>
                                    <th className="px-6 py-3 font-medium">Fuel Used</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {vehicle.trips.map((trip) => (
                                    <tr key={trip.id}>
                                        <td className="px-6 py-3 text-foreground">{formatDateTime(trip.startTime)}</td>
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
                                    </tr>
                                ))}
                                {vehicle.trips.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="px-6 py-10 text-center text-muted-foreground">
                                            No trips recorded yet.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </Card>
            ) : null}

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
