<?php

namespace App\Mail;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->invoice->invoice_number} — Promoseven Real Estate",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-issued',
            with: ['invoice' => $this->invoice],
        );
    }

    public function attachments(): array
    {
        $this->invoice->loadMissing('payments', 'tenant');

        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $this->invoice])
                  ->setPaper('a4', 'portrait');

        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $pdf->output(), "invoice-{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
