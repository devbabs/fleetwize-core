import { Head } from '@inertiajs/react';

import CompanyLayout from '@/layouts/company/company-layout';

interface Driver {
    id: number;
    name: string;
}

interface Scorecard {
    id: number;

    period_type: string;
    period_start: string;
    period_end: string;

    score: number;
    grade: string;

    trip_count: number;

    risk_score: number;
    speeding_score: number;
    eco_score: number;
    fatigue_score: number;
    distraction_score: number;

    distance_km: number;
    duration_seconds: number;

    overspeed_events: number;
    severe_overspeed_events: number;
    overspeed_duration_seconds: number;

    harsh_acceleration_events: number;
    harsh_braking_events: number;
    harsh_cornering_events: number;

    high_engine_load_events: number;

    fatigue_events: number;
    continuous_driving_seconds: number;

    idle_minutes: number;

    score_breakdown?: {
        risk?: number;
        speeding?: number;
        eco?: number;
        fatigue?: number;
        distraction?: number;
    };
}

interface Props {
    driver: Driver;
    latest_scorecard: Scorecard | null;
    scorecards: Scorecard[];
}

export default function Show({
    driver,
    latest_scorecard,
    scorecards,
}: Props) {
    return (
        <CompanyLayout title={`${driver.name} - Driver Scorecard`}>
            <Head title={`${driver.name} - Driver Scorecard`} />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold">
                        {driver.name}
                    </h1>

                    <p className="text-gray-500">
                        Driver Performance
                    </p>
                </div>

                <div className="grid grid-cols-5 gap-4">
                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Overall Score
                        </div>

                        <div className="text-3xl font-bold">
                            {latest_scorecard?.score ?? '-'}
                        </div>

                        <div className="text-sm">
                            Grade {latest_scorecard?.grade ?? '-'}
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Risk
                        </div>

                        <div className="text-3xl font-bold">
                            {latest_scorecard?.risk_score ?? '-'}
                        </div>

                        <div className="text-xs text-muted-foreground">
                            Braking & Cornering
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Speeding
                        </div>

                        <div className="text-3xl font-bold">
                            {latest_scorecard?.speeding_score ?? '-'}
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Eco
                        </div>

                        <div className="text-3xl font-bold">
                            {latest_scorecard?.eco_score ?? '-'}
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Fatigue
                        </div>

                        <div className="text-3xl font-bold">
                            {latest_scorecard?.fatigue_score ?? '-'}
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-4 gap-4">
                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Distraction
                        </div>

                        <div className="text-3xl font-bold">
                            {latest_scorecard?.distraction_score ?? '-'}
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Overspeed Events
                        </div>

                        <div className="text-3xl font-bold">
                            {latest_scorecard?.overspeed_events ?? 0}
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Fatigue Events
                        </div>

                        <div className="text-3xl font-bold">
                            {latest_scorecard?.fatigue_events ?? 0}
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Idle Minutes
                        </div>

                        <div className="text-3xl font-bold">
                            {latest_scorecard?.idle_minutes ?? 0}
                        </div>
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border">
                    <table className="w-full">
                        <thead>
                            <tr className="border-b">
                                <th className="p-3 text-left">
                                    Period
                                </th>

                                <th className="p-3 text-left">
                                    Overall
                                </th>

                                <th className="p-3 text-left">
                                    Risk
                                </th>

                                <th className="p-3 text-left">
                                    Speeding
                                </th>

                                <th className="p-3 text-left">
                                    Eco
                                </th>

                                <th className="p-3 text-left">
                                    Fatigue
                                </th>

                                <th className="p-3 text-left">
                                    Distraction
                                </th>

                                <th className="p-3 text-left">
                                    Grade
                                </th>

                                <th className="p-3 text-left">
                                    Trips
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            {scorecards.map((scorecard) => (
                                <tr
                                    key={scorecard.id}
                                    className="border-b"
                                >
                                    <td className="p-3 capitalize">
                                        {scorecard.period_type}
                                    </td>

                                    <td className="p-3 font-medium">
                                        {scorecard.score}
                                    </td>

                                    <td className="p-3">
                                        {scorecard.risk_score}
                                    </td>

                                    <td className="p-3">
                                        {scorecard.speeding_score}
                                    </td>

                                    <td className="p-3">
                                        {scorecard.eco_score}
                                    </td>

                                    <td className="p-3">
                                        {scorecard.fatigue_score}
                                    </td>

                                    <td className="p-3">
                                        {scorecard.distraction_score}
                                    </td>

                                    <td className="p-3">
                                        {scorecard.grade}
                                    </td>

                                    <td className="p-3">
                                        {scorecard.trip_count}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </CompanyLayout>
    );
}