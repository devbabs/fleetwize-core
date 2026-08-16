import { Head } from '@inertiajs/react';

import { Pagination } from '@/components/company/pagination';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import CompanyLayout from '@/layouts/company/company-layout';
import type { Paginated } from '@/types/pagination';

type Issue = {
    id: number;
    vehicle: string;
    summary: string;
    description: string | null;
    priority: string;
    status: string;
    reportedAt: string | null;
    reportedBy: string | null;
};

function formatDateTime(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function priorityBadge(priority: string) {
    const normalized = priority.toLowerCase();

    if (normalized === 'high' || normalized === 'urgent') {
return <Badge variant="destructive">{priority}</Badge>;
}

    if (normalized === 'medium') {
return <Badge variant="secondary">{priority}</Badge>;
}

    return <Badge variant="outline">{priority}</Badge>;
}

export default function IssuesIndex({ issues }: { issues: Paginated<Issue> }) {
    return (
        <CompanyLayout title="Issues">
            <Head title="Issues" />

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Reported</th>
                                <th className="px-6 py-3 font-medium">Vehicle</th>
                                <th className="px-6 py-3 font-medium">Summary</th>
                                <th className="px-6 py-3 font-medium">Priority</th>
                                <th className="px-6 py-3 font-medium">Status</th>
                                <th className="px-6 py-3 font-medium">By</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {issues.data.map((issue) => (
                                <tr key={issue.id}>
                                    <td className="px-6 py-3 text-muted-foreground">{formatDateTime(issue.reportedAt)}</td>
                                    <td className="px-6 py-3 font-medium text-foreground">{issue.vehicle}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{issue.summary}</td>
                                    <td className="px-6 py-3 capitalize">{priorityBadge(issue.priority)}</td>
                                    <td className="px-6 py-3 text-muted-foreground capitalize">{issue.status}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{issue.reportedBy ?? '—'}</td>
                                </tr>
                            ))}

                            {issues.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-6 py-10 text-center text-muted-foreground">
                                        No issues reported yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
                <Pagination links={issues.links} from={issues.from} to={issues.to} total={issues.total} />
            </Card>
        </CompanyLayout>
    );
}
