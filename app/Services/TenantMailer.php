<?php

namespace App\Services;

use App\Mail\InvoiceIssuedMail;
use App\Mail\InvoiceOverdueReminderMail;
use App\Mail\PaymentReceiptMail;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends tenant-facing transactional emails via the "azure" mailer. Tenant
 * email is nullable and the Azure AD app registration isn't provisioned in
 * every environment yet, so every send is guarded and failures are logged
 * rather than bubbling up — a missing/broken mailer must never block an
 * invoice being issued or a payment being recorded.
 */
class TenantMailer
{
    public function sendInvoiceIssued(Invoice $invoice): bool
    {
        $invoice->loadMissing('tenant');

        return $this->attempt(
            $invoice->tenant?->email,
            fn (string $email) => Mail::mailer('azure')->to($email)->send(new InvoiceIssuedMail($invoice)),
            "invoice issued mail for invoice #{$invoice->id}",
        );
    }

    public function sendPaymentReceipt(Payment $payment): bool
    {
        $payment->loadMissing('invoice.tenant');
        $invoice = $payment->invoice;

        return $this->attempt(
            $invoice?->tenant?->email,
            fn (string $email) => Mail::mailer('azure')->to($email)->send(new PaymentReceiptMail($payment, $invoice)),
            "payment receipt mail for payment #{$payment->id}",
        );
    }

    public function sendOverdueReminder(Invoice $invoice, int $daysOverdue): bool
    {
        $invoice->loadMissing('tenant');

        return $this->attempt(
            $invoice->tenant?->email,
            fn (string $email) => Mail::mailer('azure')->to($email)->send(new InvoiceOverdueReminderMail($invoice, $daysOverdue)),
            "overdue reminder mail for invoice #{$invoice->id}",
        );
    }

    private function attempt(?string $email, callable $send, string $context): bool
    {
        if (blank($email)) {
            return false;
        }

        try {
            $send($email);
            return true;
        } catch (Throwable $e) {
            Log::warning("Failed to send {$context}: {$e->getMessage()}");
            return false;
        }
    }
}
