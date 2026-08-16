import { Head, Link } from '@inertiajs/react';
import { ClipboardList, FilePlus2, Truck, Wrench } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import WorkshopLayout from '@/layouts/workshop/workshop-layout';

type Stats = {
    reportsThisMonth: number;
    totalReports: number;
    vehiclesServiced: number;
};

type RecentReport = {
    id: number;
    reference: string | null;
    vehicle: string;
    faultsCount: number;
    createdBy: string;
    createdAt: string | null;
};

function formatDateTime(value: string | null) {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

export default function WorkshopDashboard({ stats, recentReports }: { stats: Stats; recentReports: RecentReport[] }) {
    const tiles = [
        { label: 'Reports this month', value: stats.reportsThisMonth, icon: FilePlus2, accent: 'text-brand-green' },
        { label: 'Total reports', value: stats.totalReports, icon: ClipboardList, accent: 'text-brand-navy dark:text-white' },
        { label: 'Vehicles serviced', value: stats.vehiclesServiced, icon: Truck, accent: 'text-brand-navy dark:text-white' },
    ];

    return (
        <WorkshopLayout title="Dashboard">
            <Head title="Workshop Dashboard" />

            <div className="flex justify-end">
                <Button asChild className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                    <Link href="/workshop/reports/create">
                        <Wrench className="size-4" />
                        New diagnostic report
                    </Link>
                </Button>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                {tiles.map((tile) => (
                    <Card key={tile.label}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">{tile.label}</CardTitle>
                            <tile.icon className={`size-4 ${tile.accent}`} />
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-semibold ${tile.accent}`}>{tile.value}</div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Card className="overflow-hidden py-0">
                <CardHeader className="pt-6">
                    <CardTitle>Recent Reports</CardTitle>
                </CardHeader>
                <CardContent className="px-0 pb-6">
                    {recentReports.length === 0 ? (
                        <p className="px-6 text-sm text-muted-foreground">No diagnostic reports yet.</p>
                    ) : (
                        <div className="divide-y divide-border">
                            {recentReports.map((report) => (
                                <Link
                                    key={report.id}
                                    href={`/workshop/reports/${report.id}`}
                                    className="flex items-center justify-between gap-4 px-6 py-3 transition-colors hover:bg-muted/50"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-foreground">
                                            {report.vehicle} {report.reference ? `— ${report.reference}` : ''}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {report.faultsCount} fault(s) · by {report.createdBy} · {formatDateTime(report.createdAt)}
                                        </p>
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </WorkshopLayout>
    );
}
