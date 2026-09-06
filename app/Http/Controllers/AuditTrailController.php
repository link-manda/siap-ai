<?php

namespace App\Http\Controllers;

use App\Models\ArchiveLog;
use Spatie\Activitylog\Models\Activity;

class AuditTrailController extends Controller
{
    public function index()
    {
        return view('pages.tables.audit-trail', [
            'title' => 'Audit Trail / Log Aktivitas',
        ]);
    }
}
