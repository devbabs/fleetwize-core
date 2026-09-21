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

type Fault = {
    id: number;
    vehicle: string;
    vehicleId: number;
    code: string | null;
    meaning: string | null;
    severity: number | null;
    logTime: string | null;
    clearedAt: string | null;
};

type VehicleOption = {
    id: number;
    license_plate: string;
};

type Filters = {
    vehicle_id?: string | null;
};

type Props = {
    faults: PaginatedResponse<Fault>;
    vehicles: VehicleOption[];
    filters: Filters;
};

function formatDateTime(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function severityBadge(severity: number | null) {
    if (severity !== null && severity >= 4) {
return <Badge variant="destructive">High</Badge>;
}

    if (severity !== null && severity >= 2) {
return <Badge variant="secondary">Medium</Badge>;
}

    return <Badge variant="outline">Low</Badge>;
}

function AcknowledgeButton({ faultId }: { faultId: number }) {
    const [loading, setLoading] = useState(false);

    return (
        <Button
            size="sm"
            variant="outline"
            loading={loading}
            onClick={() => {
                setLoading(true);
                router.patch(
                    `/alarms/${faultId}/clear`,
                    {},
                    { preserveScroll: true, onFinish: () => setLoading(false) },
                );
            }}
        >
            Acknowledge
        </Button>
    );
}

export default function AlarmsIndex({faults, vehicles, filters,}: Props) {
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
                                vehicle_id: value === 'all' ? undefined : value,
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
                        <SelectItem value="all">All Vehicles</SelectItem>

                        {vehicles.map((vehicle) => (
                            <SelectItem
                                key={vehicle.id}
                                value={vehicle.id.toString()}
                            >
                                {vehicle.license_plate}
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
                                <th className="px-6 py-3 font-medium">Severity</th>
                                <th className="px-6 py-3 font-medium">Logged</th>
                                <th className="px-6 py-3 font-medium">Vehicle</th>
                                <th className="px-6 py-3 font-medium">Fault</th>
                                <th className="px-6 py-3 font-medium">Status</th>
                                <th className="px-6 py-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {faults.data.map((fault) => (
                                <tr key={fault.id}>
                                    <td className="px-6 py-3">{severityBadge(fault.severity)}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{formatDateTime(fault.logTime)}</td>
                                    <td className="px-6 py-3 font-medium text-foreground">{fault.vehicle}</td>
                                    <td className="px-6 py-3 text-muted-foreground">
                                        <span className="font-medium text-foreground">{fault.code}</span> — {fault.meaning}
                                    </td>
                                    <td className="px-6 py-3">
                                        {fault.clearedAt ? (
                                            <Badge variant="outline">Cleared</Badge>
                                        ) : (
                                            <Badge className="border-transparent bg-brand-green/15 text-brand-green">Open</Badge>
                                        )}
                                    </td>
                                    <td className="px-6 py-3 text-right">
                                        {!fault.clearedAt ? <AcknowledgeButton faultId={fault.id} /> : null}
                                    </td>
                                </tr>
                            ))}

                            {faults.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-6 py-10 text-center text-muted-foreground">
                                        No alerts logged yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
                <Pagination links={faults.links} from={faults.from} to={faults.to} total={faults.total} />
            </Card>
        </CompanyLayout>
    );
}
