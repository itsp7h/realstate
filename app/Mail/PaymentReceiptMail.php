<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment, public Invoice $invoice)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Receipt {$this->payment->payment_number} — Promoseven Real Estate",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-receipt',
            with: ['payment' => $this->payment, 'invoice' => $this->invoice],
        );
    }

    public function attachments(): array
    {
        $this->payment->loadMissing('invoice.tenant', 'ewaBill');

        $pdf = Pdf::loadView('payments.receipt', ['payment' => $this->payment, 'invoice' => $this->invoice])
                  ->setPaper('a4', 'portrait');

        return [
            Attachment::fromData(fn () => $pdf->output(), "receipt-{$this->payment->payment_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
