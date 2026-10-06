import { Head, Link } from '@inertiajs/react';

import CompanyLayout from '@/layouts/company/company-layout';

import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface Scorecard {
    id: number;

    driver_id: number;

    driver_name: string;

    period_type: string;

    period_start: string | null;

    period_end: string | null;

    trip_count: number;

    score: number;

    grade: string;

    risk_score: number;

    speeding_score: number;

    eco_score: number;

    fatigue_score: number;

    distraction_score: number;
}

interface Props {
    scorecards: {
        data: Scorecard[];
    };
}

export default function Index({
    scorecards,
}: Props) {
    return (
        <CompanyLayout title="Driver Scorecards">
            <Head title="Driver Scorecards" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold">
                        Driver Scorecards
                    </h1>

                    <p className="text-sm text-muted-foreground">
                        Review driver performance across the five pillars:
                        Risk, Speeding, Eco, Fatigue and Distraction.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Driver Performance</CardTitle>
                    </CardHeader>

                    <CardContent className="p-0">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b">
                                    <th className="p-3 text-left">
                                        Driver
                                    </th>

                                    <th className="p-3 text-left">
                                        Period
                                    </th>

                                    <th className="p-3 text-left">
                                        Trips
                                    </th>

                                    <th className="p-3 text-left">
                                        Overall
                                    </th>

                                    <th className="p-3 text-left">
                                        Pillars
                                    </th>

                                    <th className="p-3 text-left">
                                        Grade
                                    </th>

                                    <th className="p-3"></th>
                                </tr>
                            </thead>

                            <tbody>
                                {scorecards.data.map(
                                    (scorecard) => (
                                        <tr
                                            key={scorecard.id}
                                            className="border-b"
                                        >
                                            <td className="p-3 font-medium">
                                                {scorecard.driver_name}
                                            </td>

                                            <td className="p-3">
                                                <div className="capitalize">
                                                    {scorecard.period_type}
                                                </div>

                                                <div className="text-xs text-muted-foreground">
                                                    {scorecard.period_start ?? '-'}
                                                    {' – '}
                                                    {scorecard.period_end ?? '-'}
                                                </div>
                                            </td>

                                            <td className="p-3">
                                                {scorecard.trip_count}
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
                                                <div className="grid grid-cols-5 gap-3 text-xs">
                                                    <div>
                                                        <div className="text-muted-foreground">
                                                            Risk
                                                        </div>

                                                        <div className="font-medium">
                                                            {scorecard.risk_score}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div className="text-muted-foreground">
                                                            Speed
                                                        </div>

                                                        <div className="font-medium">
                                                            {scorecard.speeding_score}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div className="text-muted-foreground">
                                                            Eco
                                                        </div>

                                                        <div className="font-medium">
                                                            {scorecard.eco_score}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div className="text-muted-foreground">
                                                            Fatigue
                                                        </div>

                                                        <div className="font-medium">
                                                            {scorecard.fatigue_score}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div className="text-muted-foreground">
                                                            Distraction
                                                        </div>

                                                        <div className="font-medium">
                                                            {scorecard.distraction_score}
                                                        </div>
                                                    </div>
                                                </div>
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
                                                <Button asChild size="sm">
                                                    <Link
                                                        href={`/drivers/${scorecard.driver_id}/scorecards`}
                                                    >
                                                        View
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    )
                                )}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </CompanyLayout>
    );
}