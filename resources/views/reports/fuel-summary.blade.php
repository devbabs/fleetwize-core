<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fuel & Spend Report</title>
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
    <h1>{{ $company->name }} — Fuel & Spend Report</h1>
    <div class="meta">
        Period: {{ $start->format('M d, Y') }} to {{ $end->format('M d, Y') }} |
        Generated: {{ $generatedAt->format('M d, Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Plate</th>
                <th>Vehicle</th>
                <th class="num">Litres</th>
                <th class="num">Price / L (NGN)</th>
                <th class="num">Total Cost (NGN)</th>
                <th class="num">Odometer (km)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($entries as $entry)
                <tr>
                    <td>{{ $entry->date?->format('Y-m-d') ?? '—' }}</td>
                    <td>{{ $entry->vehicle?->license_plate ?? '—' }}</td>
                    <td>{{ trim(($entry->vehicle?->make ?? '') . ' ' . ($entry->vehicle?->model ?? '')) ?: '—' }}</td>
                    <td class="num">{{ number_format((float) $entry->litres, 2) }}</td>
                    <td class="num">{{ number_format((float) $entry->price_per_litre, 2) }}</td>
                    <td class="num">{{ number_format((float) $entry->litres * (float) $entry->price_per_litre, 2) }}</td>
                    <td class="num">{{ $entry->meter_reading ? number_format((float) $entry->meter_reading, 1) : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">No fuel entries recorded for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>