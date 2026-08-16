import { Head } from '@inertiajs/react';

import { Pagination } from '@/components/company/pagination';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CompanyLayout from '@/layouts/company/company-layout';
import type { Paginated } from '@/types/pagination';

type FuelEntry = {
    id: number;
    vehicle: string;
    date: string | null;
    litres: number;
    pricePerLitre: number;
    total: number;
    meterReading: number | null;
};

type Summary = {
    totalLitres: number;
    totalCost: number;
    entriesCount: number;
};

function formatDate(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

function formatCurrency(value: number) {
    return `₦${value.toLocaleString(undefined, { maximumFractionDigits: 0 })}`;
}

export default function FuelIndex({ entries, summary }: { entries: Paginated<FuelEntry>; summary: Summary }) {
    return (
        <CompanyLayout title="Fuel">
            <Head title="Fuel" />

            <div className="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm text-muted-foreground">Total Spend</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-semibold text-brand-navy dark:text-white">{formatCurrency(summary.totalCost)}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm text-muted-foreground">Total Litres</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-semibold text-foreground">{summary.totalLitres.toLocaleString()} L</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm text-muted-foreground">Entries</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-2xl font-semibold text-foreground">{summary.entriesCount}</p>
                    </CardContent>
                </Card>
            </div>

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Date</th>
                                <th className="px-6 py-3 font-medium">Vehicle</th>
                                <th className="px-6 py-3 font-medium">Litres</th>
                                <th className="px-6 py-3 font-medium">Price/L</th>
                                <th className="px-6 py-3 font-medium">Total</th>
                                <th className="px-6 py-3 font-medium">Odometer</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {entries.data.map((entry) => (
                                <tr key={entry.id}>
                                    <td className="px-6 py-3 text-foreground">{formatDate(entry.date)}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{entry.vehicle}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{entry.litres.toFixed(1)} L</td>
                                    <td className="px-6 py-3 text-muted-foreground">{formatCurrency(entry.pricePerLitre)}</td>
                                    <td className="px-6 py-3 text-foreground">{formatCurrency(entry.total)}</td>
                                    <td className="px-6 py-3 text-muted-foreground">
                                        {entry.meterReading !== null ? `${entry.meterReading.toLocaleString()} km` : '—'}
                                    </td>
                                </tr>
                            ))}

                            {entries.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-6 py-10 text-center text-muted-foreground">
                                        No fuel entries logged yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
                <Pagination links={entries.links} from={entries.from} to={entries.to} total={entries.total} />
            </Card>
        </CompanyLayout>
    );
}
