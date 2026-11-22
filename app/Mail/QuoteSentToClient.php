<?php

namespace App\Mail;

use App\Models\Quote;
use App\Models\SiteSetting;
use App\Services\QuotePdfService;
use App\Services\UploadService;
use App\Support\QuoteLinks;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Cotización formal para el cliente, con el PDF adjunto.
 */
class QuoteSentToClient extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Quote $quote) {}

    public function envelope(): Envelope
    {
        $settings = SiteSetting::current();

        return new Envelope(
            from: new Address(config('mail.from.address'), $settings->brand_name ?: config('mail.from.name')),
            replyTo: $settings->contact_email ? [new Address($settings->contact_email, $settings->brand_name)] : [],
            subject: "Tu cotización {$this->quote->folio}".($settings->brand_name ? " — {$settings->brand_name}" : ''),
        );
    }

    public function content(): Content
    {
        $this->quote->loadMissing(['items', 'zone']);
        $settings = SiteSetting::current();

        return new Content(view: 'emails.quote-client', with: [
            'quote' => $this->quote,
            'settings' => $settings,
            'primary' => $settings->safeColor('color_primary'),
            'travel' => QuotePdfService::travelSummary($this->quote),
            'confirmationUrl' => QuoteLinks::confirmation($this->quote),
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
