import { Head, Link } from '@inertiajs/react';
import { Activity, AlertTriangle, Truck, Wrench } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, Cell, Line, LineChart, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CompanyLayout from '@/layouts/company/company-layout';

type Stats = {
    totalVehicles: number;
    onlineNow: number;
    movingNow: number;
    openFaults: number;
    faultsToday: number;
    upcomingService: number;
};

type RecentFault = {
    id: number;
    vehicle: string | null;
    code: string | null;
    meaning: string | null;
    severity: number | null;
    logTime: string | null;
};

type Charts = {
    fleetStatus: { name: string; value: number }[];
    categoryBreakdown: { category: string; count: number }[];
    faultsTrend: { date: string; count: number }[];
};

const FLEET_STATUS_COLORS: Record<string, string> = {
    Moving: '#89c44b',
    Idle: '#f59e0b',
    Offline: '#9ca3af',
};

const tooltipStyle = {
    borderRadius: 8,
    border: '1px solid var(--border)',
    background: 'var(--card)',
    color: 'var(--card-foreground)',
    fontSize: 12,
};

export default function Overview({ stats, recentFaults, charts }: { stats: Stats; recentFaults: RecentFault[]; charts: Charts }) {
    const statTiles = [
        {
            label: 'Total Vehicles',
            value: stats.totalVehicles,
            icon: Truck,
            accent: 'text-brand-navy dark:text-white',
        },
        {
            label: 'Online Now',
            value: stats.onlineNow,
            sub: `${stats.movingNow} moving`,
            icon: Activity,
            accent: 'text-brand-green',
        },
        {
            label: 'Open Alerts',
            value: stats.openFaults,
            sub: `${stats.faultsToday} logged today`,
            icon: AlertTriangle,
            accent: 'text-amber-600 dark:text-amber-400',
        },
        {
            label: 'Upcoming Service',
            value: stats.upcomingService,
            sub: 'next 30 days',
            icon: Wrench,
            accent: 'text-brand-navy dark:text-white',
        },
    ];

    const hasVehicles = stats.totalVehicles > 0;

    return (
        <CompanyLayout title="Overview">
            <Head title="Overview" />

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {statTiles.map((tile) => (
                    <Card key={tile.label}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">{tile.label}</CardTitle>
                            <tile.icon className={`size-4 ${tile.accent}`} />
                        </CardHeader>
                        <CardContent>
                            <div className={`text-2xl font-semibold ${tile.accent}`}>{tile.value}</div>
                            {tile.sub ? <p className="mt-1 text-xs text-muted-foreground">{tile.sub}</p> : null}
                        </CardContent>
                    </Card>
                ))}
            </div>

            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Fleet Status</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {hasVehicles ? (
                            <div className="flex items-center gap-6">
                                <ResponsiveContainer width="50%" height={180}>
                                    <PieChart>
                                        <Pie
                                            data={charts.fleetStatus}
                                            dataKey="value"
                                            nameKey="name"
                                            innerRadius={45}
                                            outerRadius={75}
                                            paddingAngle={2}
                                        >
                                            {charts.fleetStatus.map((entry) => (
                                                <Cell key={entry.name} fill={FLEET_STATUS_COLORS[entry.name] ?? '#9ca3af'} />
                                            ))}
                                        </Pie>
                                        <Tooltip contentStyle={tooltipStyle} />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="space-y-2">
                                    {charts.fleetStatus.map((entry) => (
                                        <div key={entry.name} className="flex items-center gap-2 text-sm">
                                            <span
                                                className="size-2.5 rounded-full"
                                                style={{ backgroundColor: FLEET_STATUS_COLORS[entry.name] ?? '#9ca3af' }}
                                            />
                                            <span className="text-muted-foreground">{entry.name}</span>
                                            <span className="font-medium text-foreground">{entry.value}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ) : (
                            <p className="py-10 text-center text-sm text-muted-foreground">No vehicles yet.</p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Vehicles by Category</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {hasVehicles ? (
                            <ResponsiveContainer width="100%" height={180}>
                                <BarChart data={charts.categoryBreakdown}>
                                    <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="var(--border)" />
                                    <XAxis
                                        dataKey="category"
                                        tickLine={false}
                                        axisLine={false}
                                        tick={{ fontSize: 12, fill: 'var(--muted-foreground)' }}
                                        className="capitalize"
                                    />
                                    <YAxis allowDecimals={false} tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: 'var(--muted-foreground)' }} />
                                    <Tooltip contentStyle={tooltipStyle} cursor={{ fill: 'var(--muted)' }} />
                                    <Bar dataKey="count" fill="#020070" radius={[4, 4, 0, 0]} className="dark:fill-[#89c44b]" />
                                </BarChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="py-10 text-center text-sm text-muted-foreground">No vehicles yet.</p>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Alerts — Last 7 Days</CardTitle>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={180}>
                        <LineChart data={charts.faultsTrend}>
                            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="var(--border)" />
                            <XAxis dataKey="date" tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: 'var(--muted-foreground)' }} />
                            <YAxis allowDecimals={false} tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: 'var(--muted-foreground)' }} />
                            <Tooltip contentStyle={tooltipStyle} />
                            <Line type="monotone" dataKey="count" stroke="#89c44b" strokeWidth={2} dot={{ r: 3 }} />
                        </LineChart>
                    </ResponsiveContainer>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Active Alerts</CardTitle>
                </CardHeader>
                <CardContent className="px-0">
                    {recentFaults.length === 0 ? (
                        <p className="px-6 text-sm text-muted-foreground">No open alerts right now.</p>
                    ) : (
                        <div className="divide-y divide-border">
                            {recentFaults.map((fault) => (
                                <Link
                                    key={fault.id}
                                    href="/alarms"
                                    className="flex items-center justify-between gap-4 px-6 py-3 transition-colors hover:bg-muted/50"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-foreground">
                                            {fault.vehicle ?? 'Unknown vehicle'} — {fault.code ?? 'Fault'}
                                        </p>
                                        <p className="truncate text-xs text-muted-foreground">{fault.meaning ?? 'No description available.'}</p>
                                    </div>
                                    <Badge variant={fault.severity && fault.severity >= 3 ? 'destructive' : 'secondary'}>
                                        {fault.severity && fault.severity >= 3 ? 'High' : 'Medium'}
                                    </Badge>
                                </Link>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>
        </CompanyLayout>
    );
}
