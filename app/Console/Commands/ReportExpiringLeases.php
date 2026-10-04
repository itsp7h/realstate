<?php

namespace App\Console\Commands;

use App\Mail\ExpiringLeasesDigest;
use App\Models\AuditLog;
use App\Models\LeaseContract;
use App\Models\User;
use App\Services\AttentionFeed;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Watches lease expiry.
 *
 * Nothing did before: a lease could run out with no warning to anybody. The
 * only scheduled job in the app chased overdue invoices.
 *
 * What it does NOT do is contact tenants. Whether a lease is renewed, and on
 * what terms, is a conversation someone has to have — so this tells the staff,
 * and leaves the decision with them.
 *
 * Mail goes through the application's DEFAULT mailer, not the azure mailer the
 * tenant-facing mail uses directly, so an environment with MAIL_MAILER=log
 * writes the digest to the log instead of sending it. The audit-log entry is
 * written either way, so the watch leaves a trace even where mail is off.
 */
class ReportExpiringLeases extends Command
{
    protected $signature = 'leases:report-expiring
                            {--days= : How far ahead to look (default: AttentionFeed::EXPIRING_DAYS)}
                            {--no-mail : Report to the console and the audit log only}';

    protected $description = 'Find leases ending soon and email the staff a digest';

    public function handle(): int
    {
        $days = $this->option('days') === null
            ? AttentionFeed::EXPIRING_DAYS
            : (int) $this->option('days');

        if ($days < 1) {
            $this->error('--days must be at least 1.');

            return self::FAILURE;
        }

        $today = Carbon::today();
        $until = $today->copy()->addDays($days);

        $leases = LeaseContract::query()
            ->whereDate('lease_end_date', '>=', $today)
            ->whereDate('lease_end_date', '<=', $until)
            ->orderBy('lease_end_date')
            ->get();

        if ($leases->isEmpty()) {
            $this->info("No leases end in the next {$days} days.");

            return self::SUCCESS;
        }

        foreach ($leases as $lease) {
            $this->line(sprintf(
                '  %s — %s (%s) ends %s',
                $lease->lease_agreement_no ?: '#'.$lease->id,
                $lease->tenant_name,
                $lease->unit ?: '—',
                $lease->lease_end_date->format('d M Y')
            ));
        }

        $recipients = User::where('role', 'admin')
            ->whereNotNull('email')
            ->pluck('email')
            ->all();

        $mailed = false;

        if (! $this->option('no-mail') && $recipients !== []) {
            try {
                Mail::to($recipients)->send(new ExpiringLeasesDigest($leases, $days, $until));
                $mailed = true;
            } catch (\Throwable $e) {
                // A digest that cannot be sent must not fail the scheduled run
                // or lose the audit entry below.
                $this->warn('Digest could not be sent: '.$e->getMessage());
            }
        }

        AuditLog::record('leases_expiring', 'LeaseContract', null, $leases->count().' ending within '.$days.' days', [
            'days'       => $days,
            'count'      => $leases->count(),
            'mailed_to'  => $mailed ? count($recipients) : 0,
            'agreements' => $leases->pluck('lease_agreement_no')->filter()->values()->all(),
        ]);

        // The bell counts the same leases, so a run that changes nothing still
        // wants the cached figure re-derived rather than left for a minute.
        AttentionFeed::forget();

        $this->info(sprintf(
            '%d lease%s ending within %d days%s.',
            $leases->count(),
            $leases->count() === 1 ? '' : 's',
            $days,
            $mailed ? ', digest sent to '.count($recipients).' admin'.(count($recipients) === 1 ? '' : 's') : ''
        ));

        return self::SUCCESS;
    }
}
