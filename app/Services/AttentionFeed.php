<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * What needs someone's attention right now.
 *
 * The top bar's bell was a button that did nothing. This is what it counts.
 *
 * There is no notifications table, and this does not add one: everything here
 * is derived from records the app already holds — an invoice that went overdue,
 * a lease running out, a maintenance request waiting on a decision. That is a
 * deliberate trade: derived items need no write path, cannot go stale against
 * the records they describe, and cost one cached query round per minute. What
 * they cannot do is track "seen" state per user, so the bell shows a live count
 * rather than an unread one. A per-user read model is the thing to add if
 * dismissing individual items is wanted later.
 *
 * Every item is filtered by what the signed-in role may actually open, so the
 * bell never offers a route that would answer 403.
 */
class AttentionFeed
{
    /** A lease inside this many days is "running out". Matches LeaseContract::status. */
    public const EXPIRING_DAYS = 30;

    private const CACHE_SECONDS = 60;

    /**
     * @return list<array{key:string,icon:string,tone:string,title:string,sub:string,url:string,count:int}>
     */
    public function items(?User $user): array
    {
        if (! $user) {
            return [];
        }

        // Cached per role, not per user: every item is a portfolio-wide figure,
        // so two admins see the same list and should share the work.
        return Cache::remember(
            "attention-feed:{$user->role}",
            self::CACHE_SECONDS,
            fn () => $this->build($user)
        );
    }

    public function count(?User $user): int
    {
        return array_sum(array_column($this->items($user), 'count'));
    }

    /** @return list<array<string, mixed>> */
    private function build(User $user): array
    {
        $items = [];

        // The Maintenance role can only reach the maintenance module, so the
        // accounting items would be dead links for it.
        $seesPortfolio = ! $user->isMaintenance();

        if ($seesPortfolio) {
            $overdue = Invoice::where('status', 'overdue')->count();
            if ($overdue > 0) {
                $items[] = [
                    'key'   => 'overdue-invoices',
                    'icon'  => 'fa-file-invoice-dollar',
                    'tone'  => 'danger',
                    'title' => $overdue.' overdue '.($overdue === 1 ? 'invoice' : 'invoices'),
                    'sub'   => 'Past their due date and still unpaid',
                    'url'   => route('invoices.index', ['status' => 'overdue']),
                    'count' => $overdue,
                ];
            }

            $expiring = LeaseContract::query()
                ->whereDate('lease_end_date', '>=', Carbon::today())
                ->whereDate('lease_end_date', '<=', Carbon::today()->addDays(self::EXPIRING_DAYS))
                ->count();

            if ($expiring > 0) {
                $items[] = [
                    'key'   => 'expiring-leases',
                    'icon'  => 'fa-file-contract',
                    'tone'  => 'warning',
                    'title' => $expiring.' '.($expiring === 1 ? 'lease' : 'leases').' ending soon',
                    'sub'   => 'Within the next '.self::EXPIRING_DAYS.' days — renew or let lapse',
                    'url'   => route('lease-contracts.index', ['status' => 'expiring']),
                    'count' => $expiring,
                ];
            }
        }

        $awaiting = MaintenanceRequest::whereIn('status', ['waiting_supervisor', 'waiting_approval'])->count();
        if ($awaiting > 0) {
            $items[] = [
                'key'   => 'maintenance-awaiting',
                'icon'  => 'fa-screwdriver-wrench',
                'tone'  => 'warning',
                'title' => $awaiting.' '.($awaiting === 1 ? 'request' : 'requests').' awaiting a decision',
                'sub'   => 'Assessed, waiting on approval',
                'url'   => route('maintenance.index', ['status' => 'waiting_approval']),
                'count' => $awaiting,
            ];
        }

        // Deliberately NOT called "open": the dashboard already uses that word
        // for every request that is not finished, and two meanings of one word
        // in the same product is how a count starts looking wrong. These two
        // items are disjoint, so the badge's total means something.
        $unassessed = MaintenanceRequest::where('status', 'open')->count();
        if ($unassessed > 0) {
            $items[] = [
                'key'   => 'maintenance-unassessed',
                'icon'  => 'fa-wrench',
                'tone'  => 'info',
                'title' => $unassessed.' maintenance '.($unassessed === 1 ? 'request' : 'requests').' not yet assessed',
                'sub'   => 'Raised, waiting for an assessment',
                'url'   => route('maintenance.index', ['status' => 'open']),
                'count' => $unassessed,
            ];
        }

        return $items;
    }

    /** Called after a write that would change the feed. */
    public static function forget(): void
    {
        foreach (['admin', 'user', 'maintenance'] as $role) {
            Cache::forget("attention-feed:{$role}");
        }
    }
}
