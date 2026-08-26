<?php

namespace App\Http\Controllers\Api;

use App\Models\ErrorReport;
use Illuminate\Http\Request;

class ErrorReportController extends BaseController
{
    /**
     * Store a client-side error report (unauthenticated, deduplicated).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'level' => 'nullable|in:error,warning,info',
            'source' => 'nullable|in:frontend,api,server',
            'type' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
            'context' => 'nullable|array',
            'url' => 'nullable|string|max:1000',
        ]);

        $source = $validated['source'] ?? 'frontend';
        $level = $validated['level'] ?? 'error';
        $type = $validated['type'] ?? null;
        $message = $validated['message'];
        $url = $request->input('url', $request->header('referer'));

        // Deduplicate: same message (+ type + source) within the last 24h.
        $existing = ErrorReport::where('source', $source)
            ->where('type', $type)
            ->where('message', $message)
            ->where('created_at', '>=', now()->subDay())
            ->first();

        if ($existing) {
            $existing->increment('occurrences');
            $existing->update([
                'last_seen_at' => now(),
                'url' => $url ?: $existing->url,
                'context' => $validated['context'] ?? $existing->context,
            ]);
            return $this->success(['id' => $existing->id], 'Reported', 200);
        }

        $report = ErrorReport::create([
            'level' => $level,
            'source' => $source,
            'type' => $type,
            'message' => $message,
            'context' => $validated['context'] ?? null,
            'url' => $url,
            'user_id' => $request->user()?->id,
            'occurrences' => 1,
            'last_seen_at' => now(),
            'status' => 'open',
        ]);

        return $this->success(['id' => $report->id], 'Reported', 201);
    }
}