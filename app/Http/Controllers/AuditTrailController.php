<?php

namespace App\Http\Controllers;

use App\Models\ArchiveLog;

class AuditTrailController extends Controller
{
    public function index()
    {
        $query = ArchiveLog::with(['user', 'archive']);

        if (auth()->user()->role !== 'Pimpinan') {
            $query->whereHas('archive.classification', function ($q) {
                $q->where('level', '<=', 3);
            });
        }

        $logs = $query->latest()->paginate(15);

        return view('pages.tables.audit-trail', [
            'title' => 'Audit Trail / Log Aktivitas',
            'logs' => $logs,
        ]);
    }
}
