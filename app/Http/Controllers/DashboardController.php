<?php

namespace App\Http\Controllers;

use App\Interfaces\ArchiveRepositoryInterface;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected ArchiveRepositoryInterface $archiveRepository
    ) {}

    public function index()
    {
        $totalArchives = $this->archiveRepository->countTotal();

        // Next: Add more aggregations for charts

        return view('pages.dashboard.ecommerce', [
            'title' => 'SIAP-AI Dashboard', // Will rename to SIAP-AI Dashboard in frontend phase
            'totalArchives' => $totalArchives
        ]);
    }
}
