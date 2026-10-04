<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Invoice;
use App\Models\LeaseContract;
use App\Models\MaintenanceRequest;
use App\Models\PropertyUnit;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The ⌘K palette's search.
 *
 * The palette shipped with a hardcoded list of destinations and a comment
 * saying real search was "later work", while the top bar advertised "Search
 * buildings, tenants, units…". This is that search.
 *
 * Server-side by contract (CLAUDE.md): the query runs in SQL with a LIMIT and
 * only matching rows cross the wire — the palette never receives a dataset to
 * filter. Results are gated by role, so the palette cannot offer a row that
 * would answer 403 when opened.
 */
class SearchController extends Controller
{
    /** Rows per group. Enough to recognise the one you meant, few enough to scan. */
    private const PER_GROUP = 5;

    private const MIN_QUERY = 2;

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < self::MIN_QUERY) {
            return response()->json(['query' => $q, 'groups' => []]);
        }

        $user = $request->user();
        $groups = [];

        // A group is offered only where the role can open the record it links
        // to, asked area by area rather than role by role — the same capability
        // methods the sidebar and the palette's jump list gate on. Maintenance
        // gets its own module and nothing else; an Accountant gets invoices and
        // no portfolio, because every portfolio row here links to a show page
        // that would answer 403.
        if ($user->canAccessPortfolio()) {
            $groups[] = $this->group('Buildings', 'fa-building', $this->buildings($q));
            $groups[] = $this->group('Units', 'fa-door-open', $this->units($q));
            $groups[] = $this->group('Tenants', 'fa-users', $this->tenants($q));
            $groups[] = $this->group('Leases', 'fa-file-contract', $this->leases($q));
        }

        if ($user->canAccessAccounting()) {
            $groups[] = $this->group('Invoices', 'fa-file-invoice', $this->invoices($q));
        }

        if ($user->canAccessMaintenance()) {
            $groups[] = $this->group('Maintenance', 'fa-screwdriver-wrench', $this->maintenance($q));
        }

        return response()->json([
            'query'  => $q,
            'groups' => array_values(array_filter($groups, fn ($g) => $g['items'] !== [])),
        ]);
    }

    private function group(string $label, string $icon, array $items): array
    {
        return ['label' => $label, 'icon' => $icon, 'items' => $items];
    }

    /** LIKE pattern with the wildcards escaped, so a literal % cannot widen the search. */
    private function like(string $q): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q).'%';
    }

    private function buildings(string $q): array
    {
        $like = $this->like($q);

        return Building::query()
            ->where(fn ($w) => $w->where('property_name', 'like', $like)
                ->orWhere('property_code', 'like', $like)
                ->orWhere('area', 'like', $like))
            ->orderBy('property_name')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Building $b) => [
                'title' => $b->property_name,
                'sub'   => collect([$b->property_code, $b->area, $b->city])->filter()->implode(' · '),
                'url'   => route('buildings.show', $b),
            ])
            ->all();
    }

    private function units(string $q): array
    {
        $like = $this->like($q);

        return PropertyUnit::query()
            ->with('building')
            ->where(fn ($w) => $w->where('unit_name', 'like', $like)
                ->orWhere('property_code', 'like', $like)
                ->orWhere('unit_type', 'like', $like))
            ->orderBy('unit_name')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (PropertyUnit $u) => [
                'title' => $u->unit_name,
                'sub'   => collect([$u->building?->property_name ?? $u->property_name, $u->unit_type])->filter()->implode(' · '),
                'url'   => route('property-units.show', $u),
            ])
            ->all();
    }

    private function tenants(string $q): array
    {
        $like = $this->like($q);

        return Tenant::query()
            ->where(fn ($w) => $w->where('name', 'like', $like)
                ->orWhere('tenant_code', 'like', $like)
                ->orWhere('phone', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('company_name', 'like', $like))
            ->orderBy('name')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Tenant $t) => [
                'title' => $t->name,
                'sub'   => collect([$t->tenant_code, $t->phone])->filter()->implode(' · '),
                'url'   => route('tenants.show', $t),
            ])
            ->all();
    }

    private function leases(string $q): array
    {
        $like = $this->like($q);

        return LeaseContract::query()
            ->where(fn ($w) => $w->where('lease_agreement_no', 'like', $like)
                ->orWhere('tenant_name', 'like', $like)
                ->orWhere('unit', 'like', $like)
                ->orWhere('property_name', 'like', $like))
            ->orderByDesc('lease_end_date')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (LeaseContract $c) => [
                'title' => $c->lease_agreement_no ?: 'Lease #'.$c->id,
                'sub'   => collect([$c->tenant_name, $c->unit])->filter()->implode(' · '),
                'url'   => route('lease-contracts.show', $c),
            ])
            ->all();
    }

    private function invoices(string $q): array
    {
        $like = $this->like($q);

        return Invoice::query()
            ->where(fn ($w) => $w->where('invoice_number', 'like', $like)
                ->orWhere('tenant_name', 'like', $like)
                ->orWhere('tenant_code', 'like', $like))
            ->orderByDesc('invoice_date')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (Invoice $i) => [
                'title' => $i->invoice_number,
                'sub'   => collect([$i->tenant_name, $i->status])->filter()->implode(' · '),
                'url'   => route('invoices.show', $i),
            ])
            ->all();
    }

    private function maintenance(string $q): array
    {
        $like = $this->like($q);

        return MaintenanceRequest::query()
            ->where(fn ($w) => $w->where('job_order', 'like', $like)
                ->orWhere('tenant', 'like', $like)
                ->orWhere('property', 'like', $like)
                ->orWhere('flat', 'like', $like))
            ->orderByDesc('request_date')
            ->limit(self::PER_GROUP)
            ->get()
            ->map(fn (MaintenanceRequest $m) => [
                'title' => $m->job_order ?: 'Request #'.$m->id,
                'sub'   => collect([$m->property, $m->flat, $m->status])->filter()->implode(' · '),
                'url'   => route('maintenance.show', $m),
            ])
            ->all();
    }
}
