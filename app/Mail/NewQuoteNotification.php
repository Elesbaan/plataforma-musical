<?php

namespace App\Mail;

use App\Models\Quote;
use App\Models\SiteSetting;
use App\Services\QuotePdfService;
use App\Services\UploadService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso a la agencia (contact_email) de una nueva cotización en línea.
 */
class NewQuoteNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        $settings = SiteSetting::current();

        return new Envelope(
            from: new Address(config('mail.from.address'), $settings->brand_name ?: config('mail.from.name')),
            replyTo: [new Address($this->quote->client_email, $this->quote->client_name)],
            subject: "Nueva cotización {$this->quote->folio} — {$this->quote->client_name}",
        );
    }

    public function content(): Content
    {
        $this->quote->loadMissing(['items', 'zone']);
        $settings = SiteSetting::current();

        return new Content(view: 'emails.quote-agency', with: [
            'quote' => $this->quote,
            'settings' => $settings,
            'primary' => $settings->safeColor('color_primary'),
            'travel' => QuotePdfService::travelSummary($this->quote),
        ]);
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        if (! $this->quote->pdf_path) {
            return [];
        }

        return [
            Attachment::fromStorageDisk(UploadService::DISK, $this->quote->pdf_path)
                ->as("{$this->quote->folio}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
