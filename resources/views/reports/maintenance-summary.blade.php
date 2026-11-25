<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Maintenance & Health Compliance</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 18px; margin-bottom: 4px; color: #0f172a; }
        .meta { font-size: 10px; color: #64748b; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background-color: #f1f5f9; font-weight: bold; font-size: 10px; text-transform: uppercase; }
        .num { text-align: right; }
        .empty { text-align: center; padding: 20px; color: #64748b; }
    </style>
</head>
<body>
    <h1>{{ $company->name }} — Maintenance & Health Compliance</h1>
    <div class="meta">Generated: {{ $generatedAt->format('M d, Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Plate</th>
                <th>Vehicle</th>
                <th>Category</th>
                <th class="num">Odometer (km)</th>
                <th class="num">Open Faults</th>
                <th>Last / Next Service</th>
                <th>Service Title</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($vehicles as $vehicle)
                @php $latestService = $serviceEntries->get($vehicle->id)?->first(); @endphp
                <tr>
                    <td>{{ $vehicle->license_plate }}</td>
                    <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
                    <td style="text-transform: capitalize;">{{ $vehicle->category ?? '—' }}</td>
                    <td class="num">{{ number_format((float) ($vehicle->trackerState?->odometer ?? $vehicle->mileage ?? 0), 1) }}</td>
                    <td class="num">{{ $vehicle->faults_count ?? 0 }}</td>
                    <td>{{ $latestService?->starts_at?->format('Y-m-d') ?? 'None scheduled' }}</td>
                    <td>{{ $latestService?->title ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">No vehicles found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>