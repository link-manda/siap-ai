<?php

namespace App\Http\Controllers;

use App\Models\ArchiveLog;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index()
    {
        $logs = ArchiveLog::with(['user', 'archive'])->latest()->paginate(15);

        return view('pages.tables.audit-trail', [
            'title' => 'Audit Trail / Log Aktivitas',
            'logs' => $logs
        ]);
    }
}
