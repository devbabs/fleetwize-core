import { Head, Link } from '@inertiajs/react';
import { Building2, Car, Users } from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/admin-layout';

type Stats = {
    totalCompanies: number;
    totalAgents: number;
    totalVehicles: number;
};

type RecentCompany = {
    id: number;
    name: string;
    slug: string;
    vehiclesCount: number;
    createdAt: string | null;
};

function formatDate(value: string | null) {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

export default function AdminDashboard({ stats, recentCompanies }: { stats: Stats; recentCompanies: RecentCompany[] }) {
    const tiles = [
        { label: 'Companies', value: stats.totalCompanies, icon: Building2, accent: 'text-brand-navy dark:text-white' },
        { label: 'Agents', value: stats.totalAgents, icon: Users, accent: 'text-brand-navy dark:text-white' },
        { label: 'Vehicles (platform-wide)', value: stats.totalVehicles, icon: Car, accent: 'text-brand-green' },
    ];

    return (
        <AdminLayout title="Dashboard">
            <Head title="Admin Dashboard" />

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
                    <CardTitle>Recent Companies</CardTitle>
                </CardHeader>
                <CardContent className="px-0 pb-6">
                    {recentCompanies.length === 0 ? (
                        <p className="px-6 text-sm text-muted-foreground">No companies yet.</p>
                    ) : (
                        <div className="divide-y divide-border">
                            {recentCompanies.map((company) => (
                                <div key={company.id} className="flex items-center justify-between gap-4 px-6 py-3">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-foreground">{company.name}</p>
                                        <p className="truncate text-xs text-muted-foreground">
                                            {company.slug} · {company.vehiclesCount} vehicle(s) · {formatDate(company.createdAt)}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            <div className="flex gap-3">
                <Link href="/admin/companies" className="text-sm text-brand-navy underline dark:text-brand-green">
                    Manage companies
                </Link>
                <Link href="/admin/agents" className="text-sm text-brand-navy underline dark:text-brand-green">
                    Manage agents
                </Link>
            </div>
        </AdminLayout>
    );
}
