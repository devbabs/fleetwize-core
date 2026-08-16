import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Download } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import WorkshopLayout from '@/layouts/workshop/workshop-layout';

type ReportDetail = {
    id: number;
    reference: string | null;
    vehicle: { licensePlate: string | null; make: string | null; model: string | null; vin: string | null } | null;
    createdBy: string;
    createdAt: string | null;
    faults: {
        id: number;
        severity: string;
        errorCode: string | null;
        description: string | null;
        remark: string | null;
    }[];
};

function formatDateTime(value: string | null) {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function severityBadge(severity: string) {
    if (severity === 'critical' || severity === 'major') {
return <Badge variant="destructive" className="capitalize">{severity}</Badge>;
}

    if (severity === 'medium') {
return <Badge variant="secondary" className="capitalize">{severity}</Badge>;
}

    return <Badge variant="outline" className="capitalize">{severity}</Badge>;
}

export default function WorkshopReportShow({ report }: { report: ReportDetail }) {
    return (
        <WorkshopLayout title={report.reference ?? `Report #${report.id}`}>
            <Head title={report.reference ?? `Report #${report.id}`} />

            <Link href="/workshop/reports" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft className="size-4" /> Back to reports
            </Link>

            <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-semibold text-foreground">
                        {report.vehicle?.licensePlate ?? 'Vehicle'} {report.reference ? `— ${report.reference}` : ''}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        {[report.vehicle?.make, report.vehicle?.model].filter(Boolean).join(' ')} · VIN: {report.vehicle?.vin ?? '—'} · Prepared by{' '}
                        {report.createdBy} on {formatDateTime(report.createdAt)}
                    </p>
                </div>
                <Button asChild variant="outline">
                    <a href={`/workshop/reports/${report.id}/pdf`}>
                        <Download className="size-4" />
                        Download PDF
                    </a>
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Faults</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    {report.faults.map((fault) => (
                        <div key={fault.id} className="space-y-1 rounded-lg border border-border p-4">
                            <div className="flex items-center gap-2">
                                {severityBadge(fault.severity)}
                                {fault.errorCode ? <span className="text-sm font-medium text-foreground">{fault.errorCode}</span> : null}
                            </div>
                            {fault.description ? <p className="text-sm text-foreground">{fault.description}</p> : null}
                            {fault.remark ? <p className="text-xs text-muted-foreground">{fault.remark}</p> : null}
                        </div>
                    ))}
                </CardContent>
            </Card>
        </WorkshopLayout>
    );
}
