import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Wrench } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import WorkshopLayout from '@/layouts/workshop/workshop-layout';

type Fault = {
    id: number;
    code: string | null;
    meaning: string | null;
    severity: number | null;
    logTime: string | null;
    clearedAt: string | null;
};

type VehicleDetail = {
    id: number;
    licensePlate: string | null;
    make: string | null;
    model: string | null;
    year: string | null;
    vin: string | null;
};

function formatDateTime(value: string | null) {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

export default function WorkshopVehicleShow({ vehicle, faults }: { vehicle: VehicleDetail; faults: Fault[] }) {
    return (
        <WorkshopLayout title={vehicle.licensePlate ?? 'Vehicle'}>
            <Head title={vehicle.licensePlate ?? 'Vehicle'} />

            <Link href="/workshop/vehicles" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
                <ArrowLeft className="size-4" /> Back to vehicles
            </Link>

            <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-semibold text-foreground">{vehicle.licensePlate}</h2>
                    <p className="text-sm text-muted-foreground">
                        {[vehicle.year, vehicle.make, vehicle.model].filter(Boolean).join(' ')} · VIN: {vehicle.vin ?? '—'}
                    </p>
                </div>
                <Button asChild className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                    <Link href={`/workshop/reports/create?vehicle_id=${vehicle.id}`}>
                        <Wrench className="size-4" />
                        New diagnostic report
                    </Link>
                </Button>
            </div>

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Logged</th>
                                <th className="px-6 py-3 font-medium">Code</th>
                                <th className="px-6 py-3 font-medium">Description</th>
                                <th className="px-6 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {faults.map((fault) => (
                                <tr key={fault.id}>
                                    <td className="px-6 py-3 text-muted-foreground">{formatDateTime(fault.logTime)}</td>
                                    <td className="px-6 py-3 font-medium text-foreground">{fault.code}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{fault.meaning}</td>
                                    <td className="px-6 py-3">
                                        {fault.clearedAt ? <Badge variant="outline">Cleared</Badge> : <Badge variant="destructive">Open</Badge>}
                                    </td>
                                </tr>
                            ))}

                            {faults.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-6 py-10 text-center text-muted-foreground">
                                        No OBD faults recorded for this vehicle.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
            </Card>
        </WorkshopLayout>
    );
}
