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
    score: number;
    safety_score: number;
    efficiency_score: number;
    grade: string;
    trip_count: number;
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
                        Review driver safety and efficiency performance.
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
                                        Score
                                    </th>

                                    <th className="p-3 text-left">
                                        Safety
                                    </th>

                                    <th className="p-3 text-left">
                                        Efficiency
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
                                            <td className="p-3">
                                                {
                                                    scorecard.driver_name
                                                }
                                            </td>

                                            <td className="p-3 capitalize">
                                                {
                                                    scorecard.period_type
                                                }
                                            </td>

                                            <td className="p-3">
                                                {
                                                    scorecard.trip_count
                                                }
                                            </td>

                                            <td className="p-3">
                                                {
                                                    scorecard.score
                                                }
                                            </td>

                                            <td className="p-3">
                                                {
                                                    scorecard.safety_score
                                                }
                                            </td>

                                            <td className="p-3">
                                                {
                                                    scorecard.efficiency_score
                                                }
                                            </td>

                                            <td className="p-3">
                                                {
                                                    scorecard.grade
                                                }
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