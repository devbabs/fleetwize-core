<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Expiry Reminder</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.5;">
    @php
        $badgeBg = $isExpired ? '#fee2e2' : ($isUrgent ? '#fef3c7' : '#e0f2fe');
        $badgeColor = $isExpired ? '#b91c1c' : ($isUrgent ? '#92400e' : '#0369a1');
        $accentBorder = $isExpired ? '#dc2626' : ($isUrgent ? '#d97706' : '#0284c7');

        $badgeLabel = match (true) {
            $isExpired && $daysRemaining === 0 => 'EXPIRED TODAY',
            $isExpired => 'EXPIRED (' . abs($daysRemaining) . ' ' . \Illuminate\Support\Str::plural('DAY', abs($daysRemaining)) . ' AGO)',
            default => 'EXPIRES IN ' . $daysRemaining . ' ' . \Illuminate\Support\Str::plural('DAY', $daysRemaining),
        };
    @endphp

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f1f5f9; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;">

                    <!-- Header -->
                    <tr>
                        <td style="background-color: #0f172a; padding: 24px 32px; border-bottom: 4px solid {{ $accentBorder }};">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="vertical-align: middle;">
                                        <div style="font-size: 18px; font-weight: 700; color: #ffffff; letter-spacing: -0.01em;">
                                            {{ $companyName }}
                                        </div>
                                        <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                                            Fleet Compliance &amp; Document Alert
                                        </div>
                                    </td>
                                    <td align="right" style="vertical-align: middle;">
                                        <span style="display: inline-block; padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; background-color: {{ $badgeBg }}; color: {{ $badgeColor }}; letter-spacing: 0.03em;">
                                            {{ $badgeLabel }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 28px 32px;">
                            <p style="margin: 0 0 14px 0; font-size: 15px; color: #0f172a; font-weight: 600;">
                                Hello,
                            </p>

                            @if ($isExpired)
                                <p style="margin: 0 0 20px 0; font-size: 14px; color: #334155;">
                                    The <strong>{{ $docTitle }}</strong> for vehicle <strong>{{ $plate }}</strong>
                                    @if ($daysRemaining === 0)
                                        has expired <strong>today ({{ $expiresAt }})</strong>.
                                    @else
                                        expired on <strong>{{ $expiresAt }}</strong> ({{ abs($daysRemaining) }} {{ \Illuminate\Support\Str::plural('day', abs($daysRemaining)) }} ago).
                                    @endif
                                    Please renew this document immediately and update the compliance record on Fleetwize to avoid regulatory penalties or downtime.
                                </p>
                            @else
                                <p style="margin: 0 0 20px 0; font-size: 14px; color: #334155;">
                                    This is a compliance reminder that the <strong>{{ $docTitle }}</strong> for vehicle <strong>{{ $plate }}</strong> will expire in
                                    <strong>{{ $daysRemaining }} {{ \Illuminate\Support\Str::plural('day', $daysRemaining) }}</strong> on <strong>{{ $expiresAt }}</strong>.
                                    Please initiate renewal ahead of the expiry date and update the record once completed.
                                </p>
                            @endif

                            <!-- Document Summary Table -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border: 1px solid #e2e8f0; border-radius: 6px; border-collapse: separate; overflow: hidden; margin-bottom: 24px;">
                                <tr>
                                    <td colspan="2" style="background-color: #f8fafc; padding: 10px 16px; border-bottom: 1px solid #e2e8f0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #475569;">
                                        Document Details
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #64748b; width: 42%;">
                                        Vehicle
                                    </td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #0f172a; font-weight: 600;">
                                        {{ $plate }}
                                        @if ($vehicleLabel !== '—')
                                            <span style="font-weight: 400; color: #64748b;">({{ $vehicleLabel }})</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #64748b;">
                                        Assigned Driver
                                    </td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                                        {{ $driverName }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #64748b;">
                                        Document Type
                                    </td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #0f172a; font-weight: 600;">
                                        {{ $docTitle }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #64748b;">
                                        Document ID / Number
                                    </td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                                        {{ $documentNumber }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #64748b;">
                                        Last Renewed
                                    </td>
                                    <td style="padding: 10px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #0f172a;">
                                        {{ $lastRenewedAt }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 16px; font-size: 13px; color: #64748b;">
                                        Expiry Date
                                    </td>
                                    <td style="padding: 10px 16px; font-size: 13px; font-weight: 700; color: {{ $badgeColor }};">
                                        {{ $expiresAt }}
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin: 0 auto 20px auto;">
                                <tr>
                                    <td align="center" style="border-radius: 6px; background-color: #0f172a;">
                                        <a href="{{ $vehicleUrl }}" target="_blank" style="display: inline-block; padding: 12px 24px; font-size: 13px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 6px;">
                                            View &amp; Update Vehicle Documents
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; font-size: 12px; color: #64748b;">
                                Once the renewed expiry date is saved on the vehicle profile, further reminders for this cycle will automatically stop.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 16px 32px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 11px; color: #94a3b8;">
                            Sent automatically by <strong>Fleetwize Enterprise Telematics</strong> &bull; {{ $companyName }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>