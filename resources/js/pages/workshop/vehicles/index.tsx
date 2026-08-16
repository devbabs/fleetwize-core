import { Head, Link } from '@inertiajs/react';

import { Pagination } from '@/components/company/pagination';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import WorkshopLayout from '@/layouts/workshop/workshop-layout';
import type { Paginated } from '@/types/pagination';

type VehicleRow = {
    id: number;
    licensePlate: string | null;
    make: string | null;
    model: string | null;
    vin: string | null;
    openFaultsCount: number;
};

export default function WorkshopVehiclesIndex({ vehicles }: { vehicles: Paginated<VehicleRow> }) {
    return (
        <WorkshopLayout title="Vehicles">
            <Head title="Workshop Vehicles" />

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Plate No.</th>
                                <th className="px-6 py-3 font-medium">Vehicle</th>
                                <th className="px-6 py-3 font-medium">VIN</th>
                                <th className="px-6 py-3 font-medium">Open Faults</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {vehicles.data.map((vehicle) => (
                                <tr key={vehicle.id} className="transition-colors hover:bg-muted/40">
                                    <td className="px-6 py-3">
                                        <Link href={`/workshop/vehicles/${vehicle.id}`} className="font-medium text-foreground hover:underline">
                                            {vehicle.licensePlate ?? '—'}
                                        </Link>
                                    </td>
                                    <td className="px-6 py-3 text-muted-foreground">{[vehicle.make, vehicle.model].filter(Boolean).join(' ') || '—'}</td>
                                    <td className="px-6 py-3 text-muted-foreground">{vehicle.vin ?? '—'}</td>
                                    <td className="px-6 py-3">
                                        {vehicle.openFaultsCount > 0 ? (
                                            <Badge variant="destructive">{vehicle.openFaultsCount}</Badge>
                                        ) : (
                                            <span className="text-muted-foreground">—</span>
                                        )}
                                    </td>
                                </tr>
                            ))}

                            {vehicles.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-6 py-10 text-center text-muted-foreground">
                                        No vehicles yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
                <Pagination links={vehicles.links} from={vehicles.from} to={vehicles.to} total={vehicles.total} />
            </Card>
        </WorkshopLayout>
    );
}
