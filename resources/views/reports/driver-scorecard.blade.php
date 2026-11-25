<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DriverX 5-Behavior Scorecard</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { font-size: 17px; margin-bottom: 4px; color: #0f172a; }
        .meta { font-size: 10px; color: #64748b; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; }
        th { background-color: #f1f5f9; font-weight: bold; font-size: 9px; text-transform: uppercase; }
        .num { text-align: right; }
        .empty { text-align: center; padding: 20px; color: #64748b; }
    </style>
</head>
<body>
    <h1>{{ $company->name }} — DriverX 5-Behavior Scorecard</h1>
    <div class="meta">
        Period: {{ $start->format('M d, Y') }} to {{ $end->format('M d, Y') }} |
        Generated: {{ $generatedAt->format('M d, Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Driver</th>
                <th>Vehicle(s)</th>
                <th class="num">Trips</th>
                <th class="num">Dist (km)</th>
                <th class="num">Score</th>
                <th>Grade</th>
                <th class="num">Braking</th>
                <th class="num">Accel</th>
                <th class="num">Speeding</th>
                <th class="num">Cornering</th>
                <th class="num">Idle (min)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($scorecards as $row)
                <tr>
                    <td>{{ $row['driver_name'] }}</td>
                    <td>{{ $row['plate'] ?: '—' }}</td>
                    <td class="num">{{ $row['trip_count'] }}</td>
                    <td class="num">{{ number_format($row['distance_km'], 1) }}</td>
                    <td class="num"><strong>{{ $row['score'] }}</strong></td>
                    <td><strong>{{ $row['grade'] }}</strong></td>
                    <td class="num">{{ $row['harsh_braking_score'] }} ({{ $row['harsh_braking_events'] }})</td>
                    <td class="num">{{ $row['harsh_acceleration_score'] }} ({{ $row['harsh_acceleration_events'] }})</td>
                    <td class="num">{{ $row['speeding_score'] }} ({{ $row['overspeed_events'] }})</td>
                    <td class="num">{{ $row['hard_cornering_score'] }} ({{ $row['harsh_cornering_events'] }})</td>
                    <td class="num">{{ $row['idle_minutes'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="empty">No scored trips found for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>