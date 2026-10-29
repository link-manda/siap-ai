<?php

namespace App\Http\Controllers;

class AuditTrailController extends Controller
{
    public function index()
    {
        return view('pages.tables.audit-trail', [
            'title' => 'Audit Trail / Log Aktivitas',
        ]);
    }
}
