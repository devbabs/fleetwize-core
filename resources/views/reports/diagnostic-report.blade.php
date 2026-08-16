<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #020070; font-size: 12px; }
        h1 { font-size: 20px; margin-bottom: 0; }
        .muted { color: #6b7280; font-size: 11px; margin-top: 4px; }
        .meta { margin-top: 20px; display: flex; }
        .meta-item { margin-right: 32px; }
        .meta-label { font-size: 10px; text-transform: uppercase; color: #6b7280; letter-spacing: 0.03em; }
        .meta-value { font-size: 13px; font-weight: 600; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; font-size: 11px; vertical-align: top; }
        th { background: #020070; color: #ffffff; text-transform: uppercase; letter-spacing: 0.03em; }
        tr:nth-child(even) { background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 10px; font-weight: 600; text-transform: capitalize; }
        .badge-low { background: #e9f6dc; color: #4c7a22; }
        .badge-medium { background: #fef3c7; color: #92400e; }
        .badge-major { background: #ffedd5; color: #9a3412; }
        .badge-critical { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <h1>Diagnostic Report{{ $report->reference ? ' — '.$report->reference : '' }}</h1>
    <p class="muted">{{ $workshop->name }} · Generated {{ $generatedAt->format('d M Y, H:i') }}</p>

    <div class="meta">
        <div class="meta-item">
            <div class="meta-label">Vehicle</div>
            <div class="meta-value">{{ $report->vehicle?->license_plate ?? '—' }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Make / Model</div>
            <div class="meta-value">{{ trim(($report->vehicle?->make ?? '').' '.($report->vehicle?->model ?? '')) ?: '—' }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">VIN</div>
            <div class="meta-value">{{ $report->vehicle?->vin ?? '—' }}</div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Prepared by</div>
            <div class="meta-value">{{ $report->createdBy->full_name }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Severity</th>
                <th>Code</th>
                <th>Description</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report->faults as $fault)
                <tr>
                    <td><span class="badge badge-{{ $fault->severity }}">{{ $fault->severity }}</span></td>
                    <td>{{ $fault->error_code ?? '—' }}</td>
                    <td>{{ $fault->description ?? '—' }}</td>
                    <td>{{ $fault->remark ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
