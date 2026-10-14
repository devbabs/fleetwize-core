import { Head } from '@inertiajs/react';

import CompanyLayout from '@/layouts/company/company-layout';

import { Badge } from '@/components/ui/badge';

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



function DriverBehaviourFramework() {
    const pillars = [
        {
            name: 'Risk',
            icon: '⚠️',
            description:
                'Assesses driving manoeuvres associated with increased driving risk.',
            metrics: [
                'Harsh braking events',
                'Harsh cornering events',
            ],
        },
        {
            name: 'Speeding',
            icon: '🏎️',
            description:
                'Assesses the frequency, severity and duration of speeding.',
            metrics: [
                'Overspeed events',
                'Severe overspeed events',
                'Overspeed duration',
            ],
        },
        {
            name: 'Distraction',
            icon: '👁️',
            description:
                'Assesses the indicators assigned to the distraction component of the current scoring model.',
            metrics: [
                'Confirm the underlying metrics from the scoring logic',
            ],
        },
        {
            name: 'Fatigue',
            icon: '🕒',
            description:
                'Assesses fatigue indicators and prolonged continuous driving.',
            metrics: [
                'Fatigue events',
                'Continuous driving duration',
            ],
        },
        {
            name: 'Eco',
            icon: '🌱',
            description:
                'Assesses driving smoothness and engine-load behaviour.',
            metrics: [
                'Harsh acceleration events',
                'High engine load events',
            ],
        },
    ];

    return (
        <div className="space-y-4 rounded-xl border p-5">
            <div>
                <h2 className="text-lg font-semibold">
                    Driver Behaviour Scoring Framework
                </h2>

                <p className="mt-1 text-sm text-muted-foreground">
                    Fleetwize assesses driver performance across
                    five pillars using the metrics defined by
                    its scoring model. These pillars provide
                    separate views of driver behaviour and
                    contribute to the overall performance profile.
                </p>
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                {pillars.map((pillar) => (
                    <div
                        key={pillar.name}
                        className="rounded-lg border p-4"
                    >
                        <div className="flex items-center gap-2">
                            <span className="text-xl">
                                {pillar.icon}
                            </span>

                            <h3 className="font-semibold">
                                {pillar.name}
                            </h3>
                        </div>

                        <p className="mt-2 text-sm text-muted-foreground">
                            {pillar.description}
                        </p>

                        <div className="mt-3">
                            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                Metrics
                            </p>

                            <ul className="mt-2 list-disc space-y-1 pl-5 text-sm">
                                {pillar.metrics.map((metric) => (
                                    <li key={metric}>
                                        {metric}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                ))}
            </div>

            <div className="rounded-lg bg-muted/50 p-4">
                <h3 className="text-sm font-semibold">
                    Understanding the scores
                </h3>

                <p className="mt-1 text-sm text-muted-foreground">
                    Pillar scores and the overall score are
                    displayed on a scale of 0–100. Refer to the
                    scoring rules to interpret individual values
                    and understand how the overall score and
                    grade are calculated.
                </p>
            </div>
        </div>
    );
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

                    <p className="text-sm text-muted-foreground">
                        Driver performance across Risk, Speeding, Eco,
                        Fatigue and Distraction.
                    </p>
                </div>

                <DriverBehaviourFramework />

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Overall Score
                        </div>

                        <div className="mt-1 text-3xl font-bold">
                            {latest_scorecard?.score ?? '-'}
                            {latest_scorecard && (
                                <span className="text-sm font-normal text-muted-foreground">
                                    /100
                                </span>
                            )}
                        </div>

                        <div className="mt-1 text-sm">
                            Grade {latest_scorecard?.grade ?? '-'}
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Risk
                        </div>

                        <div className="mt-1 text-3xl font-bold">
                            {latest_scorecard?.risk_score ?? '-'}
                        </div>

                        <div className="mt-1 text-xs text-muted-foreground">
                            Braking & Cornering
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Speeding
                        </div>

                        <div className="mt-1 text-3xl font-bold">
                            {latest_scorecard?.speeding_score ?? '-'}
                        </div>

                        <div className="mt-1 text-xs text-muted-foreground">
                            Speed Compliance
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Eco
                        </div>

                        <div className="mt-1 text-3xl font-bold">
                            {latest_scorecard?.eco_score ?? '-'}
                        </div>

                        <div className="mt-1 text-xs text-muted-foreground">
                            Acceleration & Engine Load
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Fatigue
                        </div>

                        <div className="mt-1 text-3xl font-bold">
                            {latest_scorecard?.fatigue_score ?? '-'}
                        </div>

                        <div className="mt-1 text-xs text-muted-foreground">
                            Driving & Rest
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Distraction
                        </div>

                        <div className="mt-1 text-3xl font-bold">
                            {latest_scorecard?.distraction_score ?? '-'}
                        </div>

                        <div className="mt-1 text-xs text-muted-foreground">
                            Idle Behaviour
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Overspeed Events
                        </div>

                        <div className="mt-1 text-2xl font-semibold">
                            {latest_scorecard?.overspeed_events ?? 0}
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Fatigue Events
                        </div>

                        <div className="mt-1 text-2xl font-semibold">
                            {latest_scorecard?.fatigue_events ?? 0}
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Idle Minutes
                        </div>

                        <div className="mt-1 text-2xl font-semibold">
                            {latest_scorecard?.idle_minutes ?? 0}
                        </div>
                    </div>

                    <div className="rounded-lg border p-4">
                        <div className="text-sm text-muted-foreground">
                            Trips
                        </div>

                        <div className="mt-1 text-2xl font-semibold">
                            {latest_scorecard?.trip_count ?? 0}
                        </div>
                    </div>
                </div>

                <div>
                    <h2 className="text-lg font-semibold">
                        Scorecard History
                    </h2>

                    <p className="text-sm text-muted-foreground">
                        Historical daily, weekly and monthly driver performance.
                    </p>
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
                                    <td className="p-3">
                                        <div className="font-medium capitalize">
                                            {scorecard.period_type}
                                        </div>

                                        <div className="text-xs text-muted-foreground">
                                            {scorecard.period_start ?? '-'}
                                            {' – '}
                                            {scorecard.period_end ?? '-'}
                                        </div>
                                    </td>

                                    <td className="p-3">
                                        <span className="font-semibold">
                                            {scorecard.score}
                                        </span>

                                        <span className="text-xs text-muted-foreground">
                                            /100
                                        </span>
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
                                        <Badge
                                            variant={
                                                scorecard.grade === 'A'
                                                    ? 'default'
                                                    : scorecard.grade === 'B'
                                                        ? 'secondary'
                                                        : scorecard.grade === 'C'
                                                            ? 'outline'
                                                            : 'destructive'
                                            }
                                        >
                                            {scorecard.grade}
                                        </Badge>
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