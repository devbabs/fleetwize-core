import { Head } from '@inertiajs/react';

import { Pagination } from '@/components/company/pagination';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CompanyLayout from '@/layouts/company/company-layout';
import type { Paginated } from '@/types/pagination';

type MaintenanceEntry = {
    id: number;
    vehicle: string;
    vehicleId: number;
    startsAt: string | null;
    endsAt: string | null;
    comments: string | null;
    tasks: string[];
};

function formatDateTime(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function EntryTable({ entries, emptyLabel }: { entries: MaintenanceEntry[]; emptyLabel: string }) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-sm">
                <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                    <tr>
                        <th className="px-6 py-3 font-medium">Vehicle</th>
                        <th className="px-6 py-3 font-medium">Scheduled</th>
                        <th className="px-6 py-3 font-medium">Completed</th>
                        <th className="px-6 py-3 font-medium">Tasks</th>
                        <th className="px-6 py-3 font-medium">Notes</th>
                    </tr>
                </thead>
                <tbody className="divide-y divide-border">
                    {entries.map((entry) => (
                        <tr key={entry.id}>
                            <td className="px-6 py-3 font-medium text-foreground">{entry.vehicle}</td>
                            <td className="px-6 py-3 text-muted-foreground">{formatDateTime(entry.startsAt)}</td>
                            <td className="px-6 py-3 text-muted-foreground">{formatDateTime(entry.endsAt)}</td>
                            <td className="px-6 py-3 text-muted-foreground">{entry.tasks.length ? entry.tasks.join(', ') : '—'}</td>
                            <td className="px-6 py-3 text-muted-foreground">{entry.comments ?? '—'}</td>
                        </tr>
                    ))}

                    {entries.length === 0 ? (
                        <tr>
                            <td colSpan={5} className="px-6 py-10 text-center text-muted-foreground">
                                {emptyLabel}
                            </td>
                        </tr>
                    ) : null}
                </tbody>
            </table>
        </div>
    );
}

export default function MaintenanceIndex({
    overdue,
    upcoming,
    history,
}: {
    overdue: MaintenanceEntry[];
    upcoming: MaintenanceEntry[];
    history: Paginated<MaintenanceEntry>;
}) {
    return (
        <CompanyLayout title="Maintenance">
            <Head title="Maintenance" />

            <Card className="overflow-hidden py-0">
                <CardHeader className="pt-6">
                    <CardTitle className="flex items-center gap-2">
                        Overdue
                        {overdue.length > 0 ? <Badge variant="destructive">{overdue.length}</Badge> : null}
                    </CardTitle>
                </CardHeader>
                <CardContent className="px-0 pb-6">
                    <EntryTable entries={overdue} emptyLabel="Nothing overdue." />
                </CardContent>
            </Card>

            <Card className="overflow-hidden py-0">
                <CardHeader className="pt-6">
                    <CardTitle>Upcoming (next 30 days)</CardTitle>
                </CardHeader>
                <CardContent className="px-0 pb-6">
                    <EntryTable entries={upcoming} emptyLabel="Nothing scheduled in the next 30 days." />
                </CardContent>
            </Card>

            <Card className="overflow-hidden py-0">
                <CardHeader className="pt-6">
                    <CardTitle>Service History</CardTitle>
                </CardHeader>
                <CardContent className="px-0 pb-0">
                    <EntryTable entries={history.data} emptyLabel="No completed service records yet." />
                    <Pagination links={history.links} from={history.from} to={history.to} total={history.total} />
                </CardContent>
            </Card>
        </CompanyLayout>
    );
}
