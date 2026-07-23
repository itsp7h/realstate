<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\TenantMailer;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Rent invoices are due on their invoice_date (there's no separate due_date
 * column — see TenantLedgerService, which treats invoice_date the same way
 * for ageing). Any issued/partially_paid invoice past that date with a
 * balance still owing is overdue; this marks it as such and emails the
 * tenant a reminder, run daily via the scheduler.
 */
class SendInvoiceReminders extends Command
{
    protected $signature = 'invoices:send-overdue-reminders';

    protected $description = 'Mark past-due invoices as overdue and email tenants a reminder';

    public function handle(TenantMailer $tenantMailer): int
    {
        $today = Carbon::today();

        $invoices = Invoice::whereIn('status', ['issued', 'partially_paid'])
            ->whereDate('invoice_date', '<', $today)
            ->with('tenant', 'payments', 'invoiceNotes')
            ->get()
            ->filter(fn (Invoice $invoice) => $invoice->balance_due > 0);

        $marked  = 0;
        $emailed = 0;

        foreach ($invoices as $invoice) {
            $invoice->updateQuietly(['status' => 'overdue']);
            $marked++;

            $daysOverdue = (int) $invoice->invoice_date->diffInDays($today);

            if ($tenantMailer->sendOverdueReminder($invoice, $daysOverdue)) {
                $emailed++;
            }
        }

        $this->info("Marked {$marked} invoice(s) overdue, emailed {$emailed} reminder(s).");

        return self::SUCCESS;
    }
}
