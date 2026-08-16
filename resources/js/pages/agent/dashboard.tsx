import { Head, Link } from '@inertiajs/react';
import { Car, UserPlus, Users } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AgentLayout from '@/layouts/agent/agent-layout';

type Stats = {
    customersOnboarded: number;
    vehiclesOnboarded: number;
    onboardedThisMonth: number;
};

type RecentCustomer = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    vehiclesCount: number;
    createdAt: string | null;
};

function formatDateTime(value: string | null) {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

export default function AgentDashboard({ stats, recentCustomers }: { stats: Stats; recentCustomers: RecentCustomer[] }) {
    const tiles = [
        { label: 'Customers onboarded', value: stats.customersOnboarded, icon: Users, accent: 'text-brand-navy dark:text-white' },
        { label: 'Vehicles onboarded', value: stats.vehiclesOnboarded, icon: Car, accent: 'text-brand-navy dark:text-white' },
        { label: 'Onboarded this month', value: stats.onboardedThisMonth, icon: UserPlus, accent: 'text-brand-green' },
    ];

    return (
        <AgentLayout title="Dashboard">
            <Head title="Agent Dashboard" />

            <div className="flex justify-end">
                <Button asChild className="bg-brand-navy text-white hover:bg-brand-navy/90 dark:bg-brand-green dark:text-brand-navy">
                    <Link href="/agent/customers/create">
                        <UserPlus className="size-4" />
                        Onboard customer
                    </Link>
                </Button>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
                {tiles.map((tile) => (
                    <Card key={tile.label}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">{tile.label}</CardTitle>
                            <tile.icon className={`size-4 ${tile.accent}`} />
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-semibold ${tile.accent}`}>{tile.value}</div>
                        </CardContent>
                    </Card>
                ))}
            </div>

            <Card className="overflow-hidden py-0">
                <CardHeader className="pt-6">
                    <CardTitle>Recently Onboarded</CardTitle>
                </CardHeader>
                <CardContent className="px-0 pb-6">
                    {recentCustomers.length === 0 ? (
                        <p className="px-6 text-sm text-muted-foreground">No customers onboarded yet.</p>
                    ) : (
                        <div className="divide-y divide-border">
                            {recentCustomers.map((customer) => (
                                <div key={customer.id} className="flex items-center justify-between gap-4 px-6 py-3">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-foreground">{customer.name}</p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {customer.email} · {customer.vehiclesCount} vehicle(s) · {formatDateTime(customer.createdAt)}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </AgentLayout>
    );
}
