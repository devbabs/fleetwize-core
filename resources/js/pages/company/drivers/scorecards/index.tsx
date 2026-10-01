import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
// import { route } from 'ziggy-js';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useVehicleLiveUpdates } from '@/hooks/use-vehicle-live-updates';
import CompanyLayout from '@/layouts/company/company-layout';
import { cn } from '@/lib/utils';

import { Link } from '@inertiajs/react';

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
        <div className="space-y-6">
            <h1 className="text-2xl font-bold">
                Driver Scorecards
            </h1>

            <div className="overflow-hidden rounded-lg border bg-white">
                <table className="w-full">
                    <thead>
                        <tr className="border-b bg-gray-50">
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
                                        <Link
                                            href={route(
                                                'company.drivers.scorecards.show',
                                                scorecard.driver_id
                                            )}
                                            className="text-blue-600"
                                        >
                                            View
                                        </Link>
                                    </td>
                                </tr>
                            )
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}