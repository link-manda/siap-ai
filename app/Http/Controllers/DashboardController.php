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
        $categoryStats = $this->archiveRepository->getCategoryStats();
        $classificationStats = $this->archiveRepository->getClassificationStats();
        $monthlyStats = $this->archiveRepository->getMonthlyStats();

        return view('pages.dashboard.ecommerce', [
            'title' => 'SIAP-AI Dashboard',
            'totalArchives' => $totalArchives,
            'categoryStats' => $categoryStats,
            'classificationStats' => $classificationStats,
            'monthlyStats' => $monthlyStats,
        ]);
    }
}
