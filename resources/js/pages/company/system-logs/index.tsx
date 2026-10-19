import { Head } from '@inertiajs/react';

import { Pagination } from '@/components/company/pagination';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import CompanyLayout from '@/layouts/company/company-layout';
import type { Paginated } from '@/types/pagination';

type Log = {
    id: number;
    user: string | null;
    event: string;
    description: string | null;
    ipAddress: string | null;
    createdAt: string | null;
};

function formatEventLabel(event: string): string {
    return event
        .replace(/[._-]/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

export default function SystemLogsIndex({
    logs,
}: {
    logs: Paginated<Log>;
}) {
    return (
        <CompanyLayout title="System Logs">
            <Head title="System Logs" />

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">User</th>
                                <th className="px-6 py-3 font-medium">Event</th>
                                <th className="px-6 py-3 font-medium">Description</th>
                                <th className="px-6 py-3 font-medium">IP Address</th>
                                <th className="px-6 py-3 font-medium">Date</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y divide-border">
                            {logs.data.map((log) => (
                                <tr key={log.id} className="hover:bg-muted/20">
                                    <td className="px-6 py-3 whitespace-nowrap font-medium text-foreground">
                                        {log.user ?? 'System'}
                                    </td>

                                    <td className="px-6 py-3 whitespace-nowrap">
                                        <Badge variant="secondary" className="font-mono text-xs">
                                            {formatEventLabel(log.event)}
                                        </Badge>
                                    </td>

                                    <td className="px-6 py-3 text-muted-foreground">
                                        {log.description ?? '—'}
                                    </td>

                                    <td className="px-6 py-3 whitespace-nowrap font-mono text-xs text-muted-foreground">
                                        {log.ipAddress ?? '—'}
                                    </td>

                                    <td className="px-6 py-3 whitespace-nowrap text-muted-foreground">
                                        {log.createdAt
                                            ? new Date(log.createdAt).toLocaleString()
                                            : '—'}
                                    </td>
                                </tr>
                            ))}

                            {logs.data.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-6 py-10 text-center text-muted-foreground"
                                    >
                                        No system logs recorded yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>

                <Pagination
                    links={logs.links}
                    from={logs.from}
                    to={logs.to}
                    total={logs.total}
                />
            </Card>
        </CompanyLayout>
    );
}