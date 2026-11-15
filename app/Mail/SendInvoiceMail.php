<?php

namespace App\Mail;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function envelope(): Envelope
    {
        $period = $this->invoice->period;

        if (preg_match('/\b(janvier|f[eé]vrier|mars|avril|mai|juin|juillet|ao[uû]t|septembre|octobre|novembre|d[eé]cembre)\b(?:\s*[-–]\s*|\s+)(\d{4})\b/iu', $period, $matches) === 1) {
            $month = mb_convert_case($matches[1], MB_CASE_TITLE, 'UTF-8');
            $period = $month.' - '.$matches[2];
        }

        return new Envelope(
            from: new Address($this->invoice->provider_email),
            subject: '[facture] '.$period,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.invoices.empty',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn (): string => Pdf::loadView('invoices.pdf', ['invoice' => $this->invoice])
                    ->setPaper('a4')
                    ->output(),
                "facture-{$this->invoice->invoice_number}.pdf",
            )
                ->withMime('application/pdf'),
        ];
    }
}
