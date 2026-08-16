import { Head, Link } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';

import { Pagination } from '@/components/company/pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import AgentLayout from '@/layouts/agent/agent-layout';
import type { Paginated } from '@/types/pagination';

type CustomerRow = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    vehiclesCount: number;
    vehicles: { id: number; licensePlate: string | null; label: string }[];
    createdAt: string | null;
};

function formatDate(value: string | null) {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

export default function AgentCustomersIndex({ customers }: { customers: Paginated<CustomerRow> }) {
    return (
        <AgentLayout title="Customers">
            <Head title="Customers" />

            <div className="flex justify-end">
                <Button asChild className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                    <Link href="/agent/customers/create">
                        <UserPlus className="size-4" />
                        Onboard customer
                    </Link>
                </Button>
            </div>

            <Card className="overflow-hidden py-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead className="border-b bg-muted/40 text-left text-xs tracking-wide text-muted-foreground uppercase">
                            <tr>
                                <th className="px-6 py-3 font-medium">Name</th>
                                <th className="px-6 py-3 font-medium">Contact</th>
                                <th className="px-6 py-3 font-medium">Vehicles</th>
                                <th className="px-6 py-3 font-medium">Onboarded</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {customers.data.map((customer) => (
                                <tr key={customer.id}>
                                    <td className="px-6 py-3 font-medium text-foreground">{customer.name}</td>
                                    <td className="px-6 py-3 text-muted-foreground">
                                        <div>{customer.email}</div>
                                        {customer.phone ? <div className="text-xs">{customer.phone}</div> : null}
                                    </td>
                                    <td className="px-6 py-3">
                                        <div className="flex flex-wrap gap-1">
                                            {customer.vehicles.map((vehicle) => (
                                                <Badge key={vehicle.id} variant="outline">
                                                    {vehicle.licensePlate ?? vehicle.label}
                                                </Badge>
                                            ))}
                                            {customer.vehicles.length === 0 ? <span className="text-muted-foreground">—</span> : null}
                                        </div>
                                    </td>
                                    <td className="px-6 py-3 text-muted-foreground">{formatDate(customer.createdAt)}</td>
                                </tr>
                            ))}

                            {customers.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-6 py-10 text-center text-muted-foreground">
                                        No customers onboarded yet.
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
                <Pagination links={customers.links} from={customers.from} to={customers.to} total={customers.total} />
            </Card>
        </AgentLayout>
    );
}
