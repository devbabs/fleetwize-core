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

interface TripScoreItem {
    id: number;
    vehicle_name: string;
    license_plate: string | null;
    start_time: string | null;
    end_time: string | null;
    distance_km: number;
    fuel_consumed: number | null;
    max_speed: number | null;
    score: number;
    grade: string;
    harsh_braking_score: number;
    harsh_acceleration_score: number;
    speeding_score: number;
    hard_cornering_score: number;
    idling_score: number;
    harsh_braking_events: number;
    harsh_acceleration_events: number;
    overspeed_events: number;
    harsh_cornering_events: number;
    idle_minutes: number;
}

interface Props {
    driver: DriverInfo;
    selected_scorecard: ScorecardRecord | null;
    trips: TripScoreItem[];
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
    selected_scorecard,
    trips,
    scorecards,
}: Props) {
    const brakingEvents =
        selected_scorecard?.harsh_braking_events ??
        selected_scorecard?.score_breakdown?.harsh_braking?.events ??
        0;

    const accelerationEvents =
        selected_scorecard?.harsh_acceleration_events ??
        selected_scorecard?.score_breakdown?.harsh_acceleration?.events ??
        0;

    const speedingEvents =
        selected_scorecard?.overspeed_events ??
        selected_scorecard?.score_breakdown?.speeding?.events ??
        0;

    const corneringEvents =
        selected_scorecard?.harsh_cornering_events ??
        selected_scorecard?.score_breakdown?.hard_cornering?.events ??
        0;

    const idleMinutes =
        selected_scorecard?.idle_minutes ??
        selected_scorecard?.score_breakdown?.idling?.idle_minutes ??
        0;

    const behaviourCards = [
        {
            label: 'Harsh Braking',
            score: selected_scorecard?.harsh_braking_score,
            metricText: `${brakingEvents} events`,
            hint: 'Sudden deceleration',
        },
        {
            label: 'Harsh Acceleration',
            score: selected_scorecard?.harsh_acceleration_score,
            metricText: `${accelerationEvents} events`,
            hint: 'Rapid throttle launches',
        },
        {
            label: 'Speeding',
            score: selected_scorecard?.speeding_score,
            metricText: `${speedingEvents} trips over limit`,
            hint: 'Max speed vs. speed limit',
        },
        {
            label: 'Hard Cornering',
            score: selected_scorecard?.hard_cornering_score,
            metricText: `${corneringEvents} events`,
            hint: 'Sharp turns at speed',
        },
        {
            label: 'Idling',
            score: selected_scorecard?.idling_score,
            metricText: `${idleMinutes} mins`,
            hint: 'Stationary engine-on time',
        },
    ];

    const periodFuelConsumed =
    selected_scorecard?.fuel_consumed !== null &&
    selected_scorecard?.fuel_consumed !== undefined
        ? Number(selected_scorecard.fuel_consumed)
        : trips.some((t) => t.fuel_consumed !== null)
        ? trips.reduce((sum, t) => sum + (t.fuel_consumed ?? 0), 0)
        : null;

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

                    {selected_scorecard && (
                        <div className="flex items-center gap-3 rounded-lg border bg-card px-4 py-2.5">
                            <div>
                                <div className="text-xs text-muted-foreground">Selected Period</div>
                                <div className="text-sm font-medium capitalize">
                                    {selected_scorecard.period_type} ({selected_scorecard.period_start ?? '-'} – {selected_scorecard.period_end ?? '-'})
                                </div>
                            </div>
                            <Badge variant={gradeBadgeVariant(selected_scorecard.grade)}>
                                Grade {selected_scorecard.grade}
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
                            <div className={`text-3xl font-bold tabular-nums ${scoreToneClass(selected_scorecard?.score)}`}>
                                {selected_scorecard?.score ?? '—'}
                                {selected_scorecard && (
                                    <span className="text-sm font-normal text-muted-foreground"> / 100</span>
                                )}
                            </div>
                            <div className="mt-1 text-xs text-muted-foreground">
                                {selected_scorecard?.trip_count ?? 0} trips evaluated
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

                {/* Individual Trips for the Selected Period */}
                <Card>
                    <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>
                                Trips in This Period ({trips.length})
                            </CardTitle>
                            <p className="text-sm text-muted-foreground">
                                Per-trip breakdown across all five driving behaviours for{' '}
                                <span className="font-medium capitalize text-foreground">
                                    {selected_scorecard?.period_type ?? 'selected'}
                                </span>{' '}
                                ({selected_scorecard?.period_start ?? '-'} – {selected_scorecard?.period_end ?? '-'}).
                            </p>
                        </div>

                        <div className="rounded-lg border bg-muted/30 px-4 py-2 text-right">
                            <div className="text-xs uppercase tracking-wide text-muted-foreground">
                                Total Fuel Consumed ({selected_scorecard?.period_type ?? 'period'})
                            </div>
                            <div className="text-lg font-bold tabular-nums text-foreground">
                                {periodFuelConsumed !== null ? `${periodFuelConsumed.toFixed(1)} L` : '—'}
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b bg-muted/30 text-xs uppercase tracking-wider text-muted-foreground">
                                        <th className="p-3 text-left">Trip Time</th>
                                        <th className="p-3 text-left">Vehicle</th>
                                        <th className="p-3 text-right">Distance</th>
                                        <th className="p-3 text-left">Overall</th>
                                        <th className="p-3 text-left">Braking</th>
                                        <th className="p-3 text-left">Accel</th>
                                        <th className="p-3 text-left">Speeding</th>
                                        <th className="p-3 text-left">Cornering</th>
                                        <th className="p-3 text-left">Idling</th>
                                        <th className="p-3 text-left">Grade</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y">
                                    {trips.length === 0 ? (
                                        <tr>
                                            <td colSpan={10} className="p-8 text-center text-muted-foreground">
                                                No scored trips found in this period.
                                            </td>
                                        </tr>
                                    ) : (
                                        trips.map((trip) => (
                                            <tr key={trip.id} className="hover:bg-muted/30">
                                                <td className="p-3 whitespace-nowrap">
                                                    <div className="font-medium">
                                                        {trip.start_time
                                                            ? new Date(trip.start_time).toLocaleTimeString([], {
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            })
                                                            : '—'}
                                                        {' – '}
                                                        {trip.end_time
                                                            ? new Date(trip.end_time).toLocaleTimeString([], {
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                            })
                                                            : '—'}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {trip.start_time
                                                            ? new Date(trip.start_time).toLocaleDateString()
                                                            : ''}
                                                    </div>
                                                </td>

                                                <td className="p-3 whitespace-nowrap">
                                                    <div className="font-medium">{trip.vehicle_name}</div>
                                                    {trip.license_plate && (
                                                        <div className="text-xs text-muted-foreground">
                                                            {trip.license_plate}
                                                        </div>
                                                    )}
                                                </td>

                                                <td className="p-3 text-right tabular-nums whitespace-nowrap">
                                                    {trip.distance_km.toFixed(1)} km
                                                </td>

                                                <td className="p-3 whitespace-nowrap tabular-nums">
                                                    <span className={`font-semibold ${scoreToneClass(trip.score)}`}>
                                                        {trip.score}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">/100</span>
                                                </td>

                                                <td className="p-3 tabular-nums" title={`${trip.harsh_braking_events} events`}>
                                                    {trip.harsh_braking_score}
                                                </td>

                                                <td className="p-3 tabular-nums" title={`${trip.harsh_acceleration_events} events`}>
                                                    {trip.harsh_acceleration_score}
                                                </td>

                                                <td className="p-3 tabular-nums" title={`${trip.overspeed_events} violations`}>
                                                    {trip.speeding_score}
                                                </td>

                                                <td className="p-3 tabular-nums" title={`${trip.harsh_cornering_events} events`}>
                                                    {trip.hard_cornering_score}
                                                </td>

                                                <td className="p-3 tabular-nums" title={`${trip.idle_minutes} mins`}>
                                                    {trip.idling_score}
                                                </td>

                                                <td className="p-3">
                                                    <Badge variant={gradeBadgeVariant(trip.grade)}>
                                                        {trip.grade}
                                                    </Badge>
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