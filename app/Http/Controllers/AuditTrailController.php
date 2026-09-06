<?php

namespace App\Http\Controllers;

use App\Models\ArchiveLog;
use Spatie\Activitylog\Models\Activity;

class AuditTrailController extends Controller
{
    public function index()
    {
        $query = ArchiveLog::with(['user', 'archive']);

        if (! auth()->user()->hasRole('Pimpinan')) {
            $query->whereHas('archive.classification', function ($q) {
                $q->where('level', '<=', 3);
            });
        }

        $logs = $query->latest()->paginate(15, ['*'], 'document_page');

        // Fetch System Logs
        $systemQuery = Activity::with(['causer', 'subject']);
        // You might want to filter system logs based on role here too if needed
        $systemLogs = $systemQuery->latest()->paginate(15, ['*'], 'system_page');

        return view('pages.tables.audit-trail', [
            'title' => 'Audit Trail / Log Aktivitas',
            'logs' => $logs,
            'systemLogs' => $systemLogs,
        ]);
    }
}
