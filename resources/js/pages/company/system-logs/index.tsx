import { Head } from '@inertiajs/react';

import CompanyLayout from '@/layouts/company/company-layout';
import { Card } from '@/components/ui/card';
import { Pagination } from '@/components/company/pagination';
import type { Paginated } from '@/types/pagination';

type Log = {
    id: number;
    user: string | null;
    event: string;
    description: string | null;
    ipAddress: string | null;
    createdAt: string | null;
};

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
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Event</th>
                                <th>Description</th>
                                <th>IP Address</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            {logs.data.map((log) => (
                                <tr key={log.id}>
                                    <td>{log.user ?? 'System'}</td>
                                    <td>{log.event}</td>
                                    <td>{log.description ?? '—'}</td>
                                    <td>{log.ipAddress ?? '—'}</td>
                                    <td>
                                        {log.createdAt
                                            ? new Date(log.createdAt).toLocaleString()
                                            : '—'}
                                    </td>
                                </tr>
                            ))}
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