<?php

namespace App\Http\Controllers;

use App\Interfaces\ArchiveRepositoryInterface;
use App\Models\ArchiveLog;
use App\Models\Category;
use App\Models\Classification;
use App\Services\ArchiveService;
use App\Services\ReportExportService;
use App\Services\WatermarkService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArchiveController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ArchiveRepositoryInterface $archiveRepository,
        protected ArchiveService $archiveService,
        protected WatermarkService $watermarkService
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'category_id' => $request->get('category_id'),
            'classification_id' => $request->get('classification_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $archives = $this->archiveRepository->getAllPaginated(10, $filters);
        $categories = Category::all();

        return view('pages.tables.basic-tables', [
            'title' => 'Manajemen Arsip',
            'archives' => $archives,
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return view('pages.form.form-elements', [
            'title' => 'Upload Arsip Terintegrasi AI',
            'categories' => Category::all(),
            'classifications' => Classification::all(),
        ]);
    }

    public function processAi(Request $request)
    {
        // Increase maximum execution time to 120 seconds to accommodate slow AI models
        set_time_limit(120);

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'extracted_text' => ['nullable', 'string'],
        ]);

        $aiDraftData = $this->archiveService->processUpload(
            $request->file('document'),
            $request->input('extracted_text')
        );

        return response()->json([
            'status' => 'success',
            'data' => $aiDraftData,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'document_date' => ['nullable', 'date'],
            'retention_date' => ['nullable', 'date'],
            'file_path' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! str_starts_with($value, 'private/archives/') || str_contains($value, '..')) {
                        $fail('File path tidak valid.');
                    }
                    if (! Storage::exists($value)) {
                        $fail('File tidak ditemukan di server.');
                    }
                },
            ],
            'file_type' => ['required', 'string'],
            'file_size' => ['nullable', 'numeric'],
            'category_id' => ['required', 'exists:categories,id'],
            'classification_id' => ['required', 'exists:classifications,id'],
            'summary' => ['nullable', 'string'],
            'extracted_text' => ['nullable', 'string'],
            'ai_model' => ['nullable', 'string'],
            'ai_confidence_score' => ['nullable', 'numeric'],
        ]);

        $this->archiveService->storeArchive($validated, Auth::id());

        return redirect()->route('archives.index')->with('success', 'Arsip berhasil disimpan!');
    }

    public function download($id)
    {
        $archive = $this->archiveRepository->findById($id);

        if (! $archive) {
            abort(404);
        }

        $this->authorize('view', $archive);

        // Observer will catch this download via a custom log if we update it,
        // but for now we manually log or let a dedicated route handle it.
        // Actually, we should manually log the download action since Observer only handles Eloquent events.

        ArchiveLog::create([
            'archive_id' => $archive->id,
            'user_id' => Auth::id(),
            'action' => 'DOWNLOAD',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Apply Security Watermark for Level 4
        if ($archive->classification->level === 4) {
            $ext = strtolower($archive->file_type);
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                $watermarkedPath = $this->watermarkService->applyWatermark(
                    $archive,
                    Auth::user(),
                    request()->ip()
                );

                // If watermark was applied successfully and returned a temp file
                if ($watermarkedPath !== Storage::path($archive->file_path)) {
                    return response()->download($watermarkedPath, $archive->title.'.'.$archive->file_type)
                        ->deleteFileAfterSend(true);
                } else {
                    Log::warning("Watermark failed or bypassed for archive ID: {$archive->id} by user ".Auth::user()->email);
                    session()->flash('warning', 'Gagal menerapkan watermark keamanan. File asli diunduh.');
                }
            } else {
                // If it's docx/xlsx, flash a warning but still download original
                session()->flash('warning', 'File asli diunduh. Format ini tidak mendukung watermark keamanan otomatis.');
            }
        }

        return Storage::download($archive->file_path, $archive->title.'.'.$archive->file_type);
    }

    public function destroy($id)
    {
        $archive = $this->archiveRepository->findById($id);

        if (! $archive) {
            abort(404);
        }

        $this->authorize('delete', $archive);

        $archive->delete(); // This triggers the observer's "deleted" event for soft deletes

        return redirect()->route('archives.index')->with('success', 'Arsip berhasil dihapus!');
    }

    public function exportExcel(Request $request, ReportExportService $exportService)
    {
        $filters = [
            'category_id' => $request->get('category_id'),
            'classification_id' => $request->get('classification_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $archives = $this->archiveRepository->getAllForExport($filters);
        $this->logBulkDownload($archives, $request);

        return $exportService->exportToExcel($archives);
    }

    public function exportPdf(Request $request, ReportExportService $exportService)
    {
        $filters = [
            'category_id' => $request->get('category_id'),
            'classification_id' => $request->get('classification_id'),
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
        ];

        $archives = $this->archiveRepository->getAllForExport($filters);
        $this->logBulkDownload($archives, $request);

        return $exportService->exportToPdf($archives);
    }

    protected function logBulkDownload($archives, Request $request)
    {
        $userId = Auth::id();
        $ip = $request->ip();
        $ua = $request->userAgent();

        $logs = [];
        $now = now();
        foreach ($archives as $archive) {
            $logs[] = [
                'archive_id' => $archive->id,
                'user_id' => $userId,
                'action' => 'DOWNLOAD',
                'ip_address' => $ip,
                'user_agent' => $ua,
                'created_at' => $now,
            ];
        }

        if (! empty($logs)) {
            ArchiveLog::insert($logs);
        }
    }
}
