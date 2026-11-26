<?php

namespace App\Mail;

use App\Models\VehicleDocument;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentExpiryReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public VehicleDocument $vehicleDocument,
        public int $daysRemaining,
        public string $milestone,
    ) {}

    public function envelope(): Envelope
    {
        $docTitle = $this->vehicleDocument->document?->title ?? 'Vehicle Document';
        $plate = $this->vehicleDocument->vehicle?->license_plate
            ?? trim("{$this->vehicleDocument->vehicle?->make} {$this->vehicleDocument->vehicle?->model}")
            ?: "Vehicle #{$this->vehicleDocument->vehicle_id}";

        $subject = match (true) {
            $this->daysRemaining <= 0 => "[EXPIRED] {$docTitle} for {$plate} has expired",
            $this->daysRemaining <= 3 => "[URGENT] {$docTitle} for {$plate} expires in {$this->daysRemaining} day(s)",
            default => "Reminder: {$docTitle} for {$plate} expires in {$this->daysRemaining} days",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $vehicle = $this->vehicleDocument->vehicle;
        $company = $vehicle?->company;

        $isExpired = $this->daysRemaining <= 0;
        $isUrgent = ! $isExpired && $this->daysRemaining <= 7;

        // Build tenant-aware URL if your app uses company subdomains; otherwise falls back to app.url
        $baseUrl = rtrim(config('app.url'), '/');
        $vehicleUrl = "{$baseUrl}/vehicles/{$vehicle?->id}";

        return new Content(
            view: 'emails.documents.expiry-reminder',
            with: [
                'companyName' => $company?->name ?? config('app.name', 'Fleetwize'),
                'docTitle' => $this->vehicleDocument->document?->title ?? 'Vehicle Document',
                'documentNumber' => $this->vehicleDocument->document_number ?: '—',
                'plate' => $vehicle?->license_plate ?? '—',
                'vehicleLabel' => trim("{$vehicle?->make} {$vehicle?->model}") ?: '—',
                'driverName' => $vehicle?->driver?->name ?? 'Unassigned',
                'lastRenewedAt' => $this->vehicleDocument->last_renewed_at
                    ? Carbon::parse($this->vehicleDocument->last_renewed_at)->format('M d, Y')
                    : '—',
                'expiresAt' => $this->vehicleDocument->expires_at
                    ? Carbon::parse($this->vehicleDocument->expires_at)->format('M d, Y')
                    : '—',
                'daysRemaining' => $this->daysRemaining,
                'isExpired' => $isExpired,
                'isUrgent' => $isUrgent,
                'vehicleUrl' => $vehicleUrl,
            ],
        );
    }
}
