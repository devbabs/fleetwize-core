<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #020070; font-size: 12px; }
        h1 { font-size: 20px; margin-bottom: 0; }
        .muted { color: #6b7280; font-size: 11px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        th { background: #020070; color: #ffffff; text-transform: uppercase; letter-spacing: 0.03em; }
        tr:nth-child(even) { background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600; }
        .badge-online { background: #e9f6dc; color: #4c7a22; }
        .badge-offline { background: #f1f2f4; color: #6b7280; }
    </style>
</head>
<body>
    <h1>{{ $company->name }} — Fleet Summary</h1>
    <p class="muted">Generated {{ $generatedAt->format('d M Y, H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Plate</th>
                <th>Vehicle</th>
                <th>Category</th>
                <th>Status</th>
                <th>Mileage</th>
                <th>Tracker</th>
                <th>Open Faults</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($vehicles as $vehicle)
                <tr>
                    <td>{{ $vehicle->license_plate ?? '—' }}</td>
                    <td>{{ trim(($vehicle->make ?? '').' '.($vehicle->model ?? '')) ?: '—' }}</td>
                    <td style="text-transform: capitalize;">{{ $vehicle->category }}</td>
                    <td style="text-transform: capitalize;">{{ $vehicle->status }}</td>
                    <td>{{ number_format($vehicle->mileage) }} km</td>
                    <td>
                        @if ($vehicle->trackerState?->isOnline())
                            <span class="badge badge-online">Online</span>
                        @else
                            <span class="badge badge-offline">Offline</span>
                        @endif
                    </td>
                    <td>{{ $vehicle->faults_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
