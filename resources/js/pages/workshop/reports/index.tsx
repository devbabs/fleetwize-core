import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import { Pagination } from '@/components/company/pagination';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import WorkshopLayout from '@/layouts/workshop/workshop-layout';
import type { Paginated } from '@/types/pagination';

type ReportRow = {
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

export default function WorkshopReportsIndex({ reports }: { reports: Paginated<ReportRow> }) {
    return (
        <WorkshopLayout title="Diagnostic Reports">
            <Head title="Diagnostic Reports" />

            <div className="flex justify-end">
                <Button asChild className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                    <Link href="/workshop/reports/create">
                        <Plus className="size-4" />
                        New report
                    </Link>
                </Button>
            </div>

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Vehicle</th>
                                <th className="px-6 py-3 font-medium">Reference</th>
                                <th className="px-6 py-3 font-medium">Faults</th>
                                <th className="px-6 py-3 font-medium">Prepared by</th>
                                <th className="px-6 py-3 font-medium">Date</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {reports.data.map((report) => (
                                <tr key={report.id} className="hover:bg-muted/40">
                                    <td className="px-6 py-3">
                                        <Link href={`/workshop/reports/${report.id}`} className="font-medium text-foreground hover:underline">
                                            {report.vehicle}
                                        </Link>
                                    </td>
                                    <td className="px-6 py-3 text-muted-foreground">{report.reference ?? '—'}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{report.faultsCount}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{report.createdBy}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{formatDateTime(report.createdAt)}</td>
                                </tr>
                            ))}

                            {reports.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-6 py-10 text-center text-muted-foreground">
                                        No diagnostic reports yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
                <Pagination links={reports.links} from={reports.from} to={reports.to} total={reports.total} />
            </Card>
        </WorkshopLayout>
    );
}
