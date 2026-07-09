<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMcpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.mcp.token');
        $provided = (string) $request->bearerToken();

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            abort(401, 'Invalid or missing MCP token.');
        }

        return $next($request);
    }
}
