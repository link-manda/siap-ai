<?php

namespace App\Http\Controllers;

use App\Services\ArchiveService;

class DashboardController extends Controller
{
    public function __construct(
        protected ArchiveService $archiveService
    ) {}

    public function index()
    {
        $stats = $this->archiveService->getDashboardStats();

        return view('pages.dashboard.ecommerce', array_merge([
            'title' => 'SIAP-AI Dashboard',
        ], $stats));
    }
}
