<?php

namespace App\Mcp\Tools;

use App\Models\MaintenanceRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('maintenance-status')]
#[Description('Summarises the maintenance department: how many requests are in each stage of the workflow (pending assessment, pending approval, approved, in progress, completed), and a list of the currently open requests so you can see what maintenance staff are actively working on right now.')]
class MaintenanceStatusTool extends Tool
{
    public function handle(Request $request): Response|ResponseFactory
    {
        $property = $request->get('property');
        $limit    = (int) ($request->get('limit') ?? 10);

        $query = MaintenanceRequest::query();
        if ($property) {
            $query->where('property', 'like', "%{$property}%");
        }

        $countsByStatus = (clone $query)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $openStatuses = ['waiting_supervisor', 'waiting_approval', 'approved', 'in_progress'];

        $openRequests = (clone $query)
            ->whereIn('status', $openStatuses)
            ->orderByDesc('date')
            ->limit($limit)
            ->get(['job_order', 'property', 'tenant', 'flat', 'status', 'date'])
            ->map(fn (MaintenanceRequest $r) => [
                'job_order' => $r->job_order,
                'property'  => $r->property,
                'tenant'    => $r->tenant,
                'flat'      => $r->flat,
                'status'    => $r->status_label,
                'date'      => $r->date?->format('Y-m-d'),
            ]);

        return Response::structured([
            'counts_by_status' => [
                'pending_assessment' => (int) ($countsByStatus['waiting_supervisor'] ?? 0),
                'pending_approval'   => (int) ($countsByStatus['waiting_approval'] ?? 0),
                'approved'           => (int) ($countsByStatus['approved'] ?? 0),
                'in_progress'        => (int) ($countsByStatus['in_progress'] ?? 0),
                'completed'          => (int) ($countsByStatus['completed'] ?? 0),
                'cancelled'          => (int) ($countsByStatus['cancelled'] ?? 0),
            ],
            'open_total' => array_sum(array_intersect_key(
                $countsByStatus->toArray(),
                array_flip($openStatuses)
            )),
            'open_requests' => $openRequests,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'property' => $schema->string()
                ->description('Optional: filter to requests for a specific property name or code (partial match).'),
            'limit' => $schema->number()
                ->description('Maximum number of open requests to list. Defaults to 10.')
                ->default(10),
        ];
    }
}
