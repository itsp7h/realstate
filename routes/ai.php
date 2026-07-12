<?php

use App\Http\Middleware\VerifyMcpToken;
use App\Mcp\Servers\CompanyStatusServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/company-status', CompanyStatusServer::class)
    ->middleware([VerifyMcpToken::class]);
