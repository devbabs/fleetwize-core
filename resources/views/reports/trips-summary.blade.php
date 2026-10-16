<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Trips & Journeys Report - {{ $company->name }}</title>
    <style>
        @page {
            margin: 25mm 15mm 20mm 15mm;
            size: a4 portrait;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .header table {
            width: 100%;
        }
        .company-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-top: 3px;
        }
        .meta-table {
            text-align: right;
            font-size: 10px;
            color: #475569;
        }
        .meta-table strong {
            color: #0f172a;
        }
        .section-title {
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
            margin-top: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .data-table th {
            background-color: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            text-align: left;
        }
        .data-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .route-col {
            max-width: 180px;
            word-wrap: break-word;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            background-color: #e2e8f0;
            color: #334155;
        }
        .vehicle-group {
            background-color: #f1f5f9;
            padding: 6px 8px;
            font-weight: 700;
            color: #0f172a;
            font-size: 11px;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }
        .footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="vertical-align: middle;">
                    <div class="company-title">{{ $company->name }}</div>
                    <div class="report-subtitle">Fleet Trips & Journey Audit</div>
                </td>
                <td class="meta-table" style="vertical-align: middle;">
                    <div><strong>Date Range:</strong> {{ $start->format('M d, Y') }} — {{ $end->format('M d, Y') }}</div>
                    <div><strong>Generated:</strong> {{ $generatedAt->format('M d, Y H:i:s') }}</div>
                </td>
            </tr>
        </table>
    </div>

    @php
        $totalTrips = 0;
        $totalDistance = 0;
    @endphp

    @forelse ($vehicles as $vehicle)
        @if ($vehicle->trips->isNotEmpty())
            <div class="vehicle-group">
                {{ $vehicle->license_plate }} &nbsp;&bull;&nbsp; {{ $vehicle->make }} {{ $vehicle->model }}
                <span style="font-weight: normal; color: #64748b; font-size: 10px;">
                    ({{ $vehicle->trips->count() }} {{ \Illuminate\Support\Str::plural('trip', $vehicle->trips->count()) }})
                </span>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Start / End Time</th>
                        <th style="width: 35%;">Route (From &rarr; To)</th>
                        <th style="width: 15%;">Driver</th>
                        <th style="width: 10%;" class="text-right">Distance</th>
                        <th style="width: 8%;" class="text-right">Duration</th>
                        <th style="width: 8%;" class="text-right">Avg Spd</th>
                        <th style="width: 9%;" class="text-right">Max Spd</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vehicle->trips as $trip)
                        @php
                            $totalTrips++;
                            $totalDistance += ($trip->distance_km ?? 0);
                            $durationMin = $trip->duration_seconds ? round($trip->duration_seconds / 60, 0) : null;
                        @endphp
                        <tr>
                            <td>
                                <div><strong>{{ $trip->start_time?->format('M d, H:i') ?? '—' }}</strong></div>
                                <div style="color: #64748b; font-size: 9px;">{{ $trip->end_time?->format('H:i') ?? '—' }}</div>
                            </td>
                            <td class="route-col">
                                <div>
                                    <span style="color: #10b981; font-weight: bold;">&bull;</span>
                                    {{ $trip->start_address ?? ($trip->start_latitude ? number_format($trip->start_latitude, 4).', '.number_format($trip->start_longitude, 4) : 'Unknown Origin') }}
                                </div>
                                <div style="margin-top: 2px;">
                                    <span style="color: #ef4444; font-weight: bold;">&bull;</span>
                                    {{ $trip->end_address ?? ($trip->end_latitude ? number_format($trip->end_latitude, 4).', '.number_format($trip->end_longitude, 4) : 'Unknown Destination') }}
                                </div>
                            </td>
                            <td>{{ $trip->driver_name ?? 'Unassigned' }}</td>
                            <td class="text-right">{{ number_format($trip->distance_km ?? 0, 1) }} km</td>
                            <td class="text-right">{{ $durationMin !== null ? $durationMin.'m' : '—' }}</td>
                            <td class="text-right">{{ $trip->average_speed_km_per_hr ? round($trip->average_speed_km_per_hr).' km/h' : '—' }}</td>
                            <td class="text-right">{{ $trip->max_speed_km_per_hr ? round($trip->max_speed_km_per_hr).' km/h' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @empty
        <p style="text-align: center; color: #64748b; margin-top: 40px;">No vehicles found.</p>
    @endforelse

    @if ($totalTrips === 0)
        <table class="data-table">
            <tbody>
                <tr>
                    <td class="text-center" style="padding: 24px; color: #64748b;">
                        No trips recorded for the selected vehicles between {{ $start->format('M d, Y') }} and {{ $end->format('M d, Y') }}.
                    </td>
                </tr>
            </tbody>
        </table>
    @else
        <div style="border-top: 1px solid #0f172a; padding-top: 8px; text-align: right; font-size: 11px;">
            <strong>Total Distance Logged:</strong> {{ number_format($totalDistance, 1) }} km across {{ $totalTrips }} recorded journeys.
        </div>
    @endif

    <div class="footer">
        Generated by Fleetwize Enterprise Telematics &bull; Confidential &bull; Page <span class="page-number"></span>
    </div>
</body>
</html>