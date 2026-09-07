<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\AuditTrailController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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
        Route::get('/trash', [ArchiveController::class, 'trash'])->name('trash')->middleware('role:Pimpinan');
        Route::post('/{id}/restore', [ArchiveController::class, 'restore'])->name('restore')->middleware('role:Pimpinan');
        Route::delete('/{id}/force', [ArchiveController::class, 'forceDestroy'])->name('force-destroy')->middleware('role:Pimpinan');
        Route::get('/export/excel', [ArchiveController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [ArchiveController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/create', [ArchiveController::class, 'create'])->name('create');
        Route::post('/process-ai', [ArchiveController::class, 'processAi'])->name('process-ai')->middleware('throttle:10,1');
        Route::post('/bulk', [ArchiveController::class, 'bulkStore'])->name('bulk-store')->middleware('throttle:15,1');
        Route::post('/', [ArchiveController::class, 'store'])->name('store');
        Route::get('/{id}/download', [ArchiveController::class, 'download'])->name('download')->middleware('throttle:60,1');
        Route::get('/{id}/stream', [ArchiveController::class, 'stream'])->name('stream')->middleware('throttle:30,1');
        Route::get('/{id}/preview', [ArchiveController::class, 'preview'])->name('preview')->middleware('throttle:60,1');
        Route::delete('/{id}', [ArchiveController::class, 'destroy'])->name('destroy');
    });

    // Audit Trail
    Route::get('/audit-trail', [AuditTrailController::class, 'index'])->name('audit.index');

    // Users Management
    Route::resource('users', UserController::class)->except(['show']);

    // Profile & Setup (placeholders from template)
    Route::get('/profile', function () {
        return view('pages.profile', ['title' => 'Profile']);
    })->name('profile');
});
