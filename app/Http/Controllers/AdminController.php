<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // ── AUDIT LOG ────────────────────────────────────────────────────────────

    public function auditLog(Request $request): View
    {
        $query = AuditLog::query()->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('entity_name', 'like', "%{$search}%")
                  ->orWhere('entity_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($entity = $request->input('entity_type')) {
            $query->where('entity_type', $entity);
        }

        $logs  = $query->paginate(50)->withQueryString();
        $stats = [
            'total'    => AuditLog::count(),
            'created'  => AuditLog::where('action', 'created')->count(),
            'updated'  => AuditLog::where('action', 'updated')->count(),
            'deleted'  => AuditLog::where('action', 'deleted')->count(),
            'imported' => AuditLog::where('action', 'imported')->count(),
        ];

        $entityTypes = AuditLog::distinct()->pluck('entity_type')->sort()->values();

        return view('admin.audit-log', compact('logs', 'stats', 'entityTypes'));
    }

    public function clearAuditLog(): RedirectResponse
    {
        AuditLog::truncate();
        return redirect()->route('admin.audit-log')->with('success', 'Audit log cleared.');
    }

    // ── ERROR LOG ────────────────────────────────────────────────────────────

    public function errorLog(Request $request): View
    {
        $entries = $this->parseLogFile();

        if ($level = $request->input('level')) {
            $entries = array_filter($entries, fn($e) => strtoupper($e['level']) === strtoupper($level));
        }

        if ($search = $request->input('search')) {
            $search  = strtolower($search);
            $entries = array_filter($entries, fn($e) => str_contains(strtolower($e['message']), $search));
        }

        $entries = array_values($entries);

        $stats = [
            'total'   => count($entries),
            'error'   => count(array_filter($entries, fn($e) => strtoupper($e['level']) === 'ERROR')),
            'warning' => count(array_filter($entries, fn($e) => strtoupper($e['level']) === 'WARNING')),
            'info'    => count(array_filter($entries, fn($e) => strtoupper($e['level']) === 'INFO')),
        ];

        // Paginate manually
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 40;
        $total   = count($entries);
        $paged   = array_slice($entries, ($page - 1) * $perPage, $perPage);
        $pages   = (int) ceil($total / $perPage);

        return view('admin.error-log', compact('paged', 'stats', 'total', 'page', 'pages'));
    }

    public function clearErrorLog(): RedirectResponse
    {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
        return redirect()->route('admin.error-log')->with('success', 'Error log cleared.');
    }

    // ── INTERNALS ────────────────────────────────────────────────────────────

    private function parseLogFile(): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile) || filesize($logFile) === 0) {
            return [];
        }

        // Read last 500 KB to avoid loading huge files
        $maxBytes = 512 * 1024;
        $fh       = fopen($logFile, 'r');
        fseek($fh, max(0, filesize($logFile) - $maxBytes));
        $content = fread($fh, $maxBytes);
        fclose($fh);

        // Split into individual log entries
        $rawEntries = preg_split('/(?=\[\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2})/', $content, -1, PREG_SPLIT_NO_EMPTY);

        $entries = [];
        foreach (array_reverse($rawEntries) as $raw) {
            $raw = trim($raw);
            if (!$raw) continue;

            // [2026-05-21 12:00:00] production.ERROR: message {"context":[]}
            if (!preg_match('/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}[^\]]*)\]\s+\w+\.(\w+):\s+(.+)/s', $raw, $m)) {
                continue;
            }

            $timestamp = $m[1];
            $level     = strtoupper($m[2]);
            $rest      = $m[3];

            // Separate first line (message) from stack trace lines
            $lines   = explode("\n", $rest);
            $message = trim(array_shift($lines));
            $trace   = trim(implode("\n", $lines));

            // Strip trailing JSON context blob from message
            $message = preg_replace('/\s+\{.*\}\s*\[\]\s*$/s', '', $message);
            $message = trim($message);

            $entries[] = [
                'timestamp' => $timestamp,
                'level'     => $level,
                'message'   => $message ?: '(empty)',
                'trace'     => $trace ?: null,
            ];
        }

        return $entries;
    }
}
