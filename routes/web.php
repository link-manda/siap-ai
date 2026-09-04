<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ArchiveController;

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Archives Routes
    Route::prefix('archives')->name('archives.')->group(function () {
        Route::get('/', [ArchiveController::class, 'index'])->name('index');
        Route::get('/create', [ArchiveController::class, 'create'])->name('create');
        Route::post('/process-ai', [ArchiveController::class, 'processAi'])->name('process-ai');
        Route::post('/', [ArchiveController::class, 'store'])->name('store');
        Route::get('/{id}/download', [ArchiveController::class, 'download'])->name('download');
        Route::delete('/{id}', [ArchiveController::class, 'destroy'])->name('destroy');
    });

    // Audit Trail
    Route::get('/audit-trail', [\App\Http\Controllers\AuditTrailController::class, 'index'])->name('audit.index');

    // Profile & Setup (placeholders from template)
    Route::get('/profile', function () {
        return view('pages.profile', ['title' => 'Profile']);
    })->name('profile');
});

// Fallback for development (keeping tailadmin UI testing routes)
Route::prefix('ui')->group(function() {
    Route::get('/calendar', function () { return view('pages.calender', ['title' => 'Calendar']); });
    Route::get('/alerts', function () { return view('pages.ui-elements.alerts', ['title' => 'Alerts']); });
    Route::get('/buttons', function () { return view('pages.ui-elements.buttons', ['title' => 'Buttons']); });
});
