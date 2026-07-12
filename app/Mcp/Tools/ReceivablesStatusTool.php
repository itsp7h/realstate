<?php

namespace App\Mcp\Tools;

use App\Services\TenantLedgerService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('accounts-receivable-status')]
#[Description('Returns the company-wide outstanding receivables position: total amount owed by tenants across all rent invoices and EWA bills, broken down by how overdue it is (under 60 days, 60-120 days, over 120 days), and the tenants with the largest outstanding balances. Amounts are in BHD.')]
class ReceivablesStatusTool extends Tool
{
    public function __construct(private TenantLedgerService $ledger) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $from = $request->get('date_from') ? Carbon::parse($request->get('date_from')) : Carbon::now()->startOfYear();
        $to   = $request->get('date_to') ? Carbon::parse($request->get('date_to')) : Carbon::today();
        $top  = (int) ($request->get('top') ?? 5);

        $groups = $this->ledger->buildGroupOutstanding($from->startOfDay(), $to->startOfDay());

        $topTenants = $groups
            ->sortByDesc('pending')
            ->take($top)
            ->map(fn (array $g) => [
                'tenant_name'    => $g['tenant']->name,
                'tenant_code'    => $g['tenant']->tenant_code,
                'outstanding_bhd' => round($g['pending'], 3),
                'under_60_days_bhd'  => round($g['lt60'], 3),
                'days_60_to_120_bhd' => round($g['b60_120'], 3),
                'over_120_days_bhd'  => round($g['gt120'], 3),
            ])
            ->values();

        return Response::structured([
            'as_of_date' => $to->format('Y-m-d'),
            'total_outstanding_bhd'   => round($groups->sum('pending'), 3),
            'under_60_days_bhd'       => round($groups->sum('lt60'), 3),
            'days_60_to_120_bhd'      => round($groups->sum('b60_120'), 3),
            'over_120_days_bhd'       => round($groups->sum('gt120'), 3),
            'tenants_with_balance_due' => $groups->count(),
            'top_tenants_by_balance'  => $topTenants,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'date_from' => $schema->string()
                ->description('Start of the date range (YYYY-MM-DD). Defaults to the start of the current year.'),
            'date_to' => $schema->string()
                ->description('End of the date range / as-of date (YYYY-MM-DD). Defaults to today.'),
            'top' => $schema->number()
                ->description('How many of the largest-balance tenants to list. Defaults to 5.')
                ->default(5),
        ];
    }
}
