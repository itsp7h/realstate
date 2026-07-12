<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\LeasingStatusTool;
use App\Mcp\Tools\MaintenanceStatusTool;
use App\Mcp\Tools\PropertyOverviewTool;
use App\Mcp\Tools\ReceivablesStatusTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Company Status Server')]
#[Version('1.0.0')]
#[Instructions('Use these tools to answer questions about how the real estate company is doing right now and what each department is currently working on: maintenance operations, leasing and occupancy, accounts receivable (money owed by tenants), and the overall property portfolio. All monetary figures are in Bahraini Dinar (BHD).')]
class CompanyStatusServer extends Server
{
    protected array $tools = [
        MaintenanceStatusTool::class,
        LeasingStatusTool::class,
        ReceivablesStatusTool::class,
        PropertyOverviewTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
