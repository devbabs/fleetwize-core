import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import CompanyLayout from '@/layouts/company/company-layout';

interface DriverInfo {
    id: number;
    name: string;
}

interface ScorecardBreakdown {
    harsh_braking?: { score?: number; events?: number };
    harsh_acceleration?: { score?: number; events?: number };
    speeding?: { score?: number; events?: number };
    hard_cornering?: { score?: number; events?: number };
    idling?: { score?: number; idle_minutes?: number };
}

interface ScorecardRecord {
    id: number;
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
    distance_km?: number;
    duration_seconds?: number;
    score_breakdown?: ScorecardBreakdown | null;
}

interface Props {
    driver: DriverInfo;
    latest_scorecard: ScorecardRecord | null;
    scorecards: ScorecardRecord[];
}

function gradeBadgeVariant(grade: string): 'default' | 'secondary' | 'outline' | 'destructive' {
    if (grade === 'A') return 'default';
    if (grade === 'B') return 'secondary';
    if (grade === 'C') return 'outline';
    return 'destructive';
}

function scoreToneClass(value: number | null | undefined): string {
    if (value === null || value === undefined) return 'text-foreground';
    const num = Number(value);
    if (!Number.isFinite(num)) return 'text-foreground';
    if (num >= 90) return 'text-emerald-600';
    if (num >= 75) return 'text-sky-600';
    if (num >= 60) return 'text-amber-600';
    return 'text-rose-600';
}

export default function Show({
    driver,
    latest_scorecard,
    scorecards,
}: Props) {
    const brakingEvents =
        latest_scorecard?.harsh_braking_events ??
        latest_scorecard?.score_breakdown?.harsh_braking?.events ??
        0;

    const accelerationEvents =
        latest_scorecard?.harsh_acceleration_events ??
        latest_scorecard?.score_breakdown?.harsh_acceleration?.events ??
        0;

    const speedingEvents =
        latest_scorecard?.overspeed_events ??
        latest_scorecard?.score_breakdown?.speeding?.events ??
        0;

    const corneringEvents =
        latest_scorecard?.harsh_cornering_events ??
        latest_scorecard?.score_breakdown?.hard_cornering?.events ??
        0;

    const idleMinutes =
        latest_scorecard?.idle_minutes ??
        latest_scorecard?.score_breakdown?.idling?.idle_minutes ??
        0;

    const behaviourCards = [
        {
            label: 'Harsh Braking',
            score: latest_scorecard?.harsh_braking_score,
            metricText: `${brakingEvents} events`,
            hint: 'Sudden deceleration',
        },
        {
            label: 'Harsh Acceleration',
            score: latest_scorecard?.harsh_acceleration_score,
            metricText: `${accelerationEvents} events`,
            hint: 'Rapid throttle launches',
        },
        {
            label: 'Speeding',
            score: latest_scorecard?.speeding_score,
            metricText: `${speedingEvents} trips over limit`,
            hint: 'Max speed vs. speed limit',
        },
        {
            label: 'Hard Cornering',
            score: latest_scorecard?.hard_cornering_score,
            metricText: `${corneringEvents} events`,
            hint: 'Sharp turns at speed',
        },
        {
            label: 'Idling',
            score: latest_scorecard?.idling_score,
            metricText: `${idleMinutes} mins`,
            hint: 'Stationary engine-on time',
        },
    ];

    return (
        <CompanyLayout title={`${driver.name} Driver Scorecard`}>
            <Head title={`${driver.name} Driver Scorecard`} />

            <div className="space-y-6">
                {/* Top Navigation & Header */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="mb-1">
                            <Button asChild variant="ghost" size="sm" className="-ml-2">
                                <Link href="/drivers-scorecard">
                                    <ArrowLeft className="mr-1.5 h-4 w-4" />
                                    Back to Scorecards
                                </Link>
                            </Button>
                        </div>
                        <h1 className="text-2xl font-bold tracking-tight">{driver.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            Performance across Harsh Braking, Harsh Acceleration, Speeding, Hard Cornering, and Idling.
                        </p>
                    </div>

                    {latest_scorecard && (
                        <div className="flex items-center gap-3 rounded-lg border bg-card px-4 py-2.5">
                            <div>
                                <div className="text-xs text-muted-foreground">Latest Period</div>
                                <div className="text-sm font-medium capitalize">
                                    {latest_scorecard.period_type} ({latest_scorecard.period_start ?? '-'} – {latest_scorecard.period_end ?? '-'})
                                </div>
                            </div>
                            <Badge variant={gradeBadgeVariant(latest_scorecard.grade)}>
                                Grade {latest_scorecard.grade}
                            </Badge>
                        </div>
                    )}
                </div>

                {/* Overall + 5 Core Behaviours Grid */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Overall Score
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className={`text-3xl font-bold tabular-nums ${scoreToneClass(latest_scorecard?.score)}`}>
                                {latest_scorecard?.score ?? '—'}
                                {latest_scorecard && (
                                    <span className="text-sm font-normal text-muted-foreground"> / 100</span>
                                )}
                            </div>
                            <div className="mt-1 text-xs text-muted-foreground">
                                {latest_scorecard?.trip_count ?? 0} trips evaluated
                            </div>
                        </CardContent>
                    </Card>

                    {behaviourCards.map((item) => (
                        <Card key={item.label}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                    {item.label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className={`text-3xl font-bold tabular-nums ${scoreToneClass(item.score)}`}>
                                    {item.score ?? '—'}
                                </div>
                                <div className="mt-1 text-xs font-medium text-foreground">
                                    {item.metricText}
                                </div>
                                <div className="text-[11px] text-muted-foreground">
                                    {item.hint}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Scorecard History Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Scorecard History</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Historical daily, weekly, and monthly scores across all five driving behaviours.
                        </p>
                    </CardHeader>

                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/30 text-xs uppercase tracking-wider text-muted-foreground">
                                        <th className="p-3 text-left">Period</th>
                                        <th className="p-3 text-left">Overall</th>
                                        <th className="p-3 text-left">Braking</th>
                                        <th className="p-3 text-left">Accel</th>
                                        <th className="p-3 text-left">Speeding</th>
                                        <th className="p-3 text-left">Cornering</th>
                                        <th className="p-3 text-left">Idling</th>
                                        <th className="p-3 text-left">Grade</th>
                                        <th className="p-3 text-right">Trips</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y">
                                    {scorecards.length === 0 ? (
                                        <tr>
                                            <td colSpan={9} className="p-8 text-center text-muted-foreground">
                                                No historical scorecards recorded for this driver yet.
                                            </td>
                                        </tr>
                                    ) : (
                                        scorecards.map((scorecard) => (
                                            <tr key={scorecard.id} className="hover:bg-muted/30">
                                                <td className="p-3 whitespace-nowrap">
                                                    <div className="font-medium capitalize">
                                                        {scorecard.period_type}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {scorecard.period_start ?? '-'} – {scorecard.period_end ?? '-'}
                                                    </div>
                                                </td>

                                                <td className="p-3 whitespace-nowrap tabular-nums">
                                                    <span className="font-semibold">{scorecard.score}</span>
                                                    <span className="text-xs text-muted-foreground">/100</span>
                                                </td>

                                                <td className="p-3 tabular-nums">{scorecard.harsh_braking_score}</td>
                                                <td className="p-3 tabular-nums">{scorecard.harsh_acceleration_score}</td>
                                                <td className="p-3 tabular-nums">{scorecard.speeding_score}</td>
                                                <td className="p-3 tabular-nums">{scorecard.hard_cornering_score}</td>
                                                <td className="p-3 tabular-nums">{scorecard.idling_score}</td>

                                                <td className="p-3">
                                                    <Badge variant={gradeBadgeVariant(scorecard.grade)}>
                                                        {scorecard.grade}
                                                    </Badge>
                                                </td>

                                                <td className="p-3 text-right tabular-nums">
                                                    {scorecard.trip_count}
                                                </td>
                                            </tr>
                                        ))
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