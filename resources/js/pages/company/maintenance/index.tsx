import { Head, router } from '@inertiajs/react';

import { Pagination } from '@/components/company/pagination';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CompanyLayout from '@/layouts/company/company-layout';
import type { Paginated } from '@/types/pagination';

type MaintenanceEntry = {
    id: number;
    vehicle: string;
    vehicleId: number;
    scheduleId: number;
    name: string;

    distanceIntervalKm: number | null;
    timeIntervalDays: number | null;

    lastMaintainedAt: string | null;
    lastOdometer: number | null;
    currentOdometer: number | null;

    nextServiceOdometer: number | null;
    nextServiceDate: string | null;

    distanceRemainingKm: number | null;
    daysRemaining: number | null;

    distanceDue: boolean;
    timeDue: boolean;

    dueReason:
        | 'distance'
        | 'time'
        | 'distance_and_time'
        | null;

    status: 'overdue' | 'upcoming';
};

type MaintenanceHistoryEntry = {
    id: number;
    vehicle: string;
    vehicleId: number;
    scheduleId: number | null;
    name: string | null;
    maintainedAt: string | null;
    odometerKm: number;
    notes: string | null;
};

function formatDateTime(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function OverdueTable({ entries }: { entries: MaintenanceEntry[] }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                    <tr>
                        <th className="px-6 py-3 font-medium">Vehicle</th>
                        <th className="px-6 py-3 font-medium">Maintenance</th>
                        <th className="px-6 py-3 font-medium">Current Odo</th>
                        <th className="px-6 py-3 font-medium">Due At Odo</th>
                        <th className="px-6 py-3 font-medium">Status</th>
                        {/* 1. Add this header */}
                        <th className="px-6 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-border">
                    {entries.map((entry) => (
                        <tr key={`${entry.vehicleId}-${entry.scheduleId}`}>
                            <td className="px-6 py-3 font-medium text-foreground">
                                {entry.vehicle}
                            </td>
                            <td className="px-6 py-3 text-muted-foreground">
                                {entry.name}
                            </td>
                            <td className="px-6 py-3 text-muted-foreground">
                                {entry.currentOdometer !== null
                                    ? `${entry.currentOdometer.toLocaleString()} km`
                                    : '—'}
                            </td>
                            <td className="px-6 py-3 text-muted-foreground">
                                {entry.nextServiceOdometer !== null
                                    ? `${entry.nextServiceOdometer.toLocaleString()} km`
                                    : '—'}
                            </td>
                            <td className="px-6 py-3">
                                <Badge variant="destructive">Overdue</Badge>
                            </td>

                            {/* 2. Your <td> goes right here as the last column of the row */}
                            <td className="px-6 py-3 text-right">
                                <div className="flex items-center justify-end gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        onClick={() => handleLogService(entry)}
                                    >
                                        Log Service
                                    </Button>
                                    <Button asChild size="sm" variant="ghost">
                                        <Link href={`/vehicles/${entry.vehicleId}`}>
                                            View Vehicle
                                        </Link>
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    ))}

                    {entries.length === 0 ? (
                        <tr>
                            {/* Make sure colSpan matches total columns (e.g. 6) */}
                            <td
                                colSpan={6}
                                className="px-6 py-10 text-center text-muted-foreground"
                            >
                                No overdue maintenance.
                            </td>
                        </tr>
                    ) : null}
                </tbody>
            </table>
        </div>
    );
}

function UpcomingTable({ entries }: { entries: MaintenanceEntry[] }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                    <tr>
                        <th className="px-6 py-3 font-medium">Vehicle</th>
                        <th className="px-6 py-3 font-medium">Maintenance</th>
                        <th className="px-6 py-3 font-medium">Last Maintained</th>
                        <th className="px-6 py-3 font-medium">Remaining</th>
                        <th className="px-6 py-3 font-medium">Status</th>
                    </tr>
                </thead>

                <tbody className="divide-y divide-border">
                    {entries.map((entry) => (
                        <tr key={`${entry.vehicleId}-${entry.scheduleId}`}>
                            <td className="px-6 py-3 font-medium text-foreground">
                                {entry.vehicle}
                            </td>

                            <td className="px-6 py-3 text-foreground">
                                {entry.name}
                            </td>

                            <td className="px-6 py-3 text-muted-foreground">
                                {entry.lastMaintainedAt
                                    ? formatDateTime(entry.lastMaintainedAt)
                                    : '—'}
                            </td>

                            <td className="px-6 py-3 text-muted-foreground">
                                {entry.distanceRemainingKm !== null
                                    ? `${entry.distanceRemainingKm.toLocaleString()} km`
                                    : entry.daysRemaining !== null
                                      ? `${entry.daysRemaining} days`
                                      : '—'}
                            </td>

                            <td className="px-6 py-3">
                                <Badge variant="outline">
                                    Upcoming
                                </Badge>
                            </td>
                        </tr>
                    ))}

                    {entries.length === 0 ? (
                        <tr>
                            <td
                                colSpan={5}
                                className="px-6 py-10 text-center text-muted-foreground"
                            >
                                Nothing scheduled in the next 30 days.
                            </td>
                        </tr>
                    ) : null}
                </tbody>
            </table>
        </div>
    );
}

function HistoryTable({
    entries,
}: {
    entries: MaintenanceHistoryEntry[];
}) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                    <tr>
                        <th className="px-6 py-3 font-medium">Vehicle</th>
                        <th className="px-6 py-3 font-medium">Maintenance</th>
                        <th className="px-6 py-3 font-medium">Completed</th>
                        <th className="px-6 py-3 font-medium">Odometer</th>
                        <th className="px-6 py-3 font-medium">Notes</th>
                    </tr>
                </thead>

                <tbody className="divide-y divide-border">
                    {entries.map((entry) => (
                        <tr key={entry.id}>
                            <td className="px-6 py-3 font-medium text-foreground">
                                {entry.vehicle}
                            </td>

                            <td className="px-6 py-3 text-foreground">
                                {entry.name ?? '—'}
                            </td>

                            <td className="px-6 py-3 text-muted-foreground">
                                {entry.maintainedAt
                                    ? formatDateTime(entry.maintainedAt)
                                    : '—'}
                            </td>

                            <td className="px-6 py-3 text-muted-foreground">
                                {entry.odometerKm.toLocaleString()} km
                            </td>

                            <td className="px-6 py-3 text-muted-foreground">
                                {entry.notes ?? '—'}
                            </td>
                        </tr>
                    ))}

                    {entries.length === 0 ? (
                        <tr>
                            <td
                                colSpan={5}
                                className="px-6 py-10 text-center text-muted-foreground"
                            >
                                No completed service records yet.
                            </td>
                        </tr>
                    ) : null}
                </tbody>
            </table>
        </div>
    );
}

function handleLogService(entry: MaintenanceEntry) {
    const defaultOdo = entry.currentOdometer ?? entry.nextServiceOdometer ?? 0;
    const confirmedOdo = window.prompt(
        `Confirm odometer reading (km) for ${entry.vehicle} — ${entry.name}:`,
        String(defaultOdo)
    );

    if (confirmedOdo === null) return;

    router.post(
        `/vehicles/${entry.vehicleId}/maintenance-records`,
        {
            maintenance_schedule_id: entry.scheduleId,
            maintained_at: new Date().toISOString().split('T')[0],
            odometer_km: Number(confirmedOdo) || defaultOdo,
            notes: `Completed ${entry.name}`,
        },
        { preserveScroll: true }
    );
}

export default function MaintenanceIndex({
    overdue,
    upcoming,
    history,
}: {
    overdue: MaintenanceEntry[];
    upcoming: MaintenanceEntry[];
    history: Paginated<MaintenanceHistoryEntry>;
}) {
    return (
        <CompanyLayout title="Maintenance">
            <Head title="Maintenance" />

            <div className="space-y-6">

                {/* Overdue */}
                <Card className="overflow-hidden py-0">
                    <CardHeader className="pt-6">
                        <CardTitle className="flex items-center gap-2">
                            Overdue

                            {overdue.length > 0 ? (
                                <Badge variant="destructive">
                                    {overdue.length}
                                </Badge>
                            ) : null}
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="px-0 pb-6">
                        <OverdueTable entries={overdue} />
                    </CardContent>
                </Card>


                {/* Upcoming */}
                <Card className="overflow-hidden py-0">
                    <CardHeader className="pt-6">
                        <CardTitle>
                            Upcoming
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="px-0 pb-6">
                        <UpcomingTable entries={upcoming} />
                    </CardContent>
                </Card>


                {/* History */}
                <Card className="overflow-hidden py-0">
                    <CardHeader className="pt-6">
                        <CardTitle>
                            Service History
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="px-0 pb-0">
                        <HistoryTable entries={history.data} />

                        <Pagination
                            links={history.links}
                            from={history.from}
                            to={history.to}
                            total={history.total}
                        />
                    </CardContent>
                </Card>

            </div>
        </CompanyLayout>
    );
}
