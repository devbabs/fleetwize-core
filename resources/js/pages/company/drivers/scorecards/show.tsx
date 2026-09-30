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
    safety_score: number;
    efficiency_score: number;

    grade: string;

    trip_count: number;

    distance_km: number;
    idle_minutes: number;

    overspeed_events: number;
    severe_overspeed_events: number;

    harsh_acceleration_events: number;
    harsh_braking_events: number;
    harsh_cornering_events: number;
}

interface Props {
    driver: Driver;
    scorecards: Scorecard[];
}

export default function Show({
    driver,
    scorecards,
}: Props) {
    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold">
                    {driver.name}
                </h1>

                <p className="text-gray-500">
                    Driver Performance
                </p>
            </div>

            <div className="grid grid-cols-4 gap-4">
                <div className="rounded-lg border p-4">
                    <div className="text-sm text-gray-500">
                        Total Scorecards
                    </div>

                    <div className="text-2xl font-bold">
                        {scorecards.length}
                    </div>
                </div>

                <div className="rounded-lg border p-4">
                    <div className="text-sm text-gray-500">
                        Latest Score
                    </div>

                    <div className="text-2xl font-bold">
                        {scorecards[0]?.score ?? '-'}
                    </div>
                </div>

                <div className="rounded-lg border p-4">
                    <div className="text-sm text-gray-500">
                        Latest Grade
                    </div>

                    <div className="text-2xl font-bold">
                        {scorecards[0]?.grade ?? '-'}
                    </div>
                </div>

                <div className="rounded-lg border p-4">
                    <div className="text-sm text-gray-500">
                        Trips
                    </div>

                    <div className="text-2xl font-bold">
                        {scorecards[0]?.trip_count ?? 0}
                    </div>
                </div>
            </div>

            <div className="overflow-hidden rounded-lg border bg-white">
                <table className="w-full">
                    <thead>
                        <tr className="border-b bg-gray-50">
                            <th className="p-3 text-left">
                                Period
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

                            <th className="p-3 text-left">
                                Distance
                            </th>

                            <th className="p-3 text-left">
                                Overspeed
                            </th>

                            <th className="p-3 text-left">
                                Idle
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        {scorecards.map(
                            (scorecard) => (
                                <tr
                                    key={scorecard.id}
                                    className="border-b"
                                >
                                    <td className="p-3">
                                        {
                                            scorecard.period_type
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
                                        {
                                            scorecard.distance_km
                                        } km
                                    </td>

                                    <td className="p-3">
                                        {
                                            scorecard.overspeed_events
                                        }
                                    </td>

                                    <td className="p-3">
                                        {
                                            scorecard.idle_minutes
                                        } min
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