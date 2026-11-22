import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CompanyLayout from '@/layouts/company/company-layout';

interface ScorecardItem {
    id: number;
    driver_id: number;
    driver_name: string;
    period_type: 'daily' | 'weekly' | 'monthly' | string;
    period_start: string | null;
    period_end: string | null;
    trip_count: number;
    score: number;
    grade: string;
    // 5 core behaviour scores (0 - 100)
    harsh_braking_score: number;
    harsh_acceleration_score: number;
    speeding_score: number;
    hard_cornering_score: number;
    idling_score: number;
    // 5 core behaviour counts / minutes
    harsh_braking_events?: number;
    harsh_acceleration_events?: number;
    overspeed_events?: number;
    harsh_cornering_events?: number;
    idle_minutes?: number;
}

interface Props {
    scorecards: {
        data: ScorecardItem[];
    };
}

const BEHAVIOUR_PILLARS = [
    { key: 'harsh_braking_score', countKey: 'harsh_braking_events', label: 'Braking', unit: 'events' },
    { key: 'harsh_acceleration_score', countKey: 'harsh_acceleration_events', label: 'Accel', unit: 'events' },
    { key: 'speeding_score', countKey: 'overspeed_events', label: 'Speeding', unit: 'trips over limit' },
    { key: 'hard_cornering_score', countKey: 'harsh_cornering_events', label: 'Cornering', unit: 'events' },
    { key: 'idling_score', countKey: 'idle_minutes', label: 'Idling', unit: 'mins' },
] as const;

function toSafeNumber(value: unknown, fallback = 0): number {
    const parsed = Number(value ?? fallback);
    return Number.isFinite(parsed) ? parsed : fallback;
}

function scoreBarColor(value: number): string {
    if (value >= 90) return 'bg-emerald-500';
    if (value >= 75) return 'bg-sky-500';
    if (value >= 60) return 'bg-amber-500';
    return 'bg-rose-500';
}

function gradeBadgeVariant(grade: string): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (grade === 'A') return 'default';
    if (grade === 'B') return 'secondary';
    if (grade === 'C') return 'outline';
    return 'destructive';
}

export default function Index({ scorecards }: Props) {
    const [periodFilter, setPeriodFilter] = useState<string>('all');

    const rows = useMemo(() => {
        if (periodFilter === 'all') return scorecards.data;
        return scorecards.data.filter((item) => item.period_type === periodFilter);
    }, [scorecards.data, periodFilter]);

    const summary = useMemo(() => {
        if (rows.length === 0) {
            return { count: 0, avgScore: '0.0', totalTrips: 0, atRiskCount: 0 };
        }

        const totalScore = rows.reduce((acc, r) => acc + toSafeNumber(r.score), 0);
        const totalTrips = rows.reduce((acc, r) => acc + toSafeNumber(r.trip_count), 0);
        const atRiskCount = rows.filter((r) => toSafeNumber(r.score) < 70).length;

        return {
            count: rows.length,
            avgScore: (totalScore / rows.length).toFixed(1),
            totalTrips,
            atRiskCount,
        };
    }, [rows]);

    return (
        <CompanyLayout title="Driver Scorecards">
            <Head title="Driver Scorecards" />

            <div className="space-y-6">
                {/* Header + Period Filter */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Driver Scorecards</h1>
                        <p className="text-sm text-muted-foreground">
                            Scored across Harsh Braking, Harsh Acceleration, Speeding, Hard Cornering, and Idling.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-1.5 rounded-lg border bg-muted/40 p-1">
                        {['all', 'daily', 'weekly', 'monthly'].map((period) => (
                            <Button
                                key={period}
                                type="button"
                                size="sm"
                                variant={periodFilter === period ? 'default' : 'ghost'}
                                className="capitalize"
                                onClick={() => setPeriodFilter(period)}
                            >
                                {period}
                            </Button>
                        ))}
                    </div>
                </div>

                {/* Top Summary KPIs */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Average Score
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tabular-nums">
                                {summary.avgScore}
                                <span className="text-sm font-normal text-muted-foreground"> / 100</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Scorecards Shown
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tabular-nums">{summary.count}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Trips Evaluated
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tabular-nums">{summary.totalTrips}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Needs Coaching (&lt; 70)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold tabular-nums text-rose-600">{summary.atRiskCount}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Scorecard Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Driver Performance</CardTitle>
                    </CardHeader>

                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/30 text-xs uppercase tracking-wider text-muted-foreground">
                                        <th className="p-3 text-left">Driver</th>
                                        <th className="p-3 text-left">Period</th>
                                        <th className="p-3 text-right">Trips</th>
                                        <th className="p-3 text-left">Overall</th>
                                        <th className="p-3 text-left">5 Core Behaviours</th>
                                        <th className="p-3 text-left">Grade</th>
                                        <th className="p-3 text-right">Action</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y">
                                    {rows.length === 0 ? (
                                        <tr>
                                            <td colSpan={7} className="p-8 text-center text-muted-foreground">
                                                No scorecards match the selected period.
                                            </td>
                                        </tr>
                                    ) : (
                                        rows.map((scorecard) => {
                                            const overall = toSafeNumber(scorecard.score);
                                            const clampedOverall = Math.max(0, Math.min(100, overall));

                                            return (
                                                <tr key={scorecard.id} className="hover:bg-muted/30">
                                                    <td className="p-3 font-medium whitespace-nowrap">
                                                        {scorecard.driver_name || 'Unassigned Driver'}
                                                    </td>

                                                    <td className="p-3 whitespace-nowrap">
                                                        <div className="font-medium capitalize">
                                                            {scorecard.period_type}
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            {scorecard.period_start ?? '-'} – {scorecard.period_end ?? '-'}
                                                        </div>
                                                    </td>

                                                    <td className="p-3 text-right font-medium tabular-nums">
                                                        {scorecard.trip_count}
                                                    </td>

                                                    <td className="p-3 whitespace-nowrap">
                                                        <div className="flex items-center gap-2">
                                                            <span className="font-semibold tabular-nums">
                                                                {overall}
                                                            </span>
                                                            <span className="text-xs text-muted-foreground">/100</span>
                                                        </div>
                                                        <div className="mt-1 h-1.5 w-20 overflow-hidden rounded-full bg-muted">
                                                            <div
                                                                className={`h-full ${scoreBarColor(overall)}`}
                                                                style={{ width: `${clampedOverall}%` }}
                                                            />
                                                        </div>
                                                    </td>

                                                    <td className="p-3">
                                                        <div className="grid min-w-[340px] grid-cols-5 gap-2 text-xs">
                                                            {BEHAVIOUR_PILLARS.map((pillar) => {
                                                                const val = toSafeNumber(scorecard[pillar.key]);
                                                                const count = toSafeNumber(scorecard[pillar.countKey]);
                                                                const clampedVal = Math.max(0, Math.min(100, val));

                                                                return (
                                                                    <div
                                                                        key={pillar.key}
                                                                        className="rounded border bg-background px-2 py-1.5"
                                                                        title={`${pillar.label}: ${val}/100 (${count} ${pillar.unit})`}
                                                                    >
                                                                        <div className="truncate text-[11px] text-muted-foreground">
                                                                            {pillar.label}
                                                                        </div>
                                                                        <div className="mt-0.5 font-semibold tabular-nums">
                                                                            {val}
                                                                        </div>
                                                                        <div className="mt-1 h-1 w-full overflow-hidden rounded-full bg-muted">
                                                                            <div
                                                                                className={`h-full ${scoreBarColor(val)}`}
                                                                                style={{ width: `${clampedVal}%` }}
                                                                            />
                                                                        </div>
                                                                    </div>
                                                                );
                                                            })}
                                                        </div>
                                                    </td>

                                                    <td className="p-3">
                                                        <Badge variant={gradeBadgeVariant(scorecard.grade)}>
                                                            {scorecard.grade}
                                                        </Badge>
                                                    </td>

                                                    <td className="p-3 text-right">
                                                        <Button asChild size="sm" variant="outline">
                                                            <Link href={`/drivers/${scorecard.driver_id}/scorecards?scorecard=${scorecard.id}`}>
                                                                View
                                                            </Link>
                                                        </Button>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </CompanyLayout>
    );
}