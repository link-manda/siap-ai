<?php

namespace App\Http\Controllers;

use App\Interfaces\ArchiveRepositoryInterface;
use App\Models\Archive;
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
use Symfony\Component\HttpFoundation\HeaderUtils;

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
        $classifications = Auth::user()->role === 'Pimpinan'
            ? Classification::all()
            : Classification::where('level', '<=', 3)->get();

        return view('pages.form.form-elements', [
            'title' => 'Upload Arsip Terintegrasi AI',
            'categories' => Category::all(),
            'classifications' => $classifications,
        ]);
    }

    public function processAi(Request $request)
    {
        // Increase maximum execution time to 120 seconds to accommodate slow AI models
        set_time_limit(120);

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
            'extracted_text' => ['nullable', 'string', 'max:20000'],
        ]);

        $aiDraftData = $this->archiveService->processUpload(
            $request->file('document'),
            $request->input('extracted_text')
        );

        $classification = Classification::find($aiDraftData['classification_id']);
        $role = Auth::user()->role ?? 'Admin';

        if ($role === 'Admin' && $classification && $classification->level === 4) {
            $aiDraftData['extracted_text'] = '[REDACTED]';
            $aiDraftData['summary'] = '[REDACTED - Level 4 Security]';
        }

        return response()->json([
            'status' => 'success',
            'data' => $aiDraftData,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Archive::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'document_date' => ['nullable', 'date'],
            'retention_date' => ['nullable', 'date'],
            'file_path' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if ((! str_starts_with($value, 'private/archives/') && ! str_starts_with($value, 'temp/archives/')) || str_contains($value, '..')) {
                        $fail('File path tidak valid.');
                    }
                    $fullPath = Storage::path($value);
                    if (! Storage::exists($value) || ! is_file($fullPath)) {
                        $fail('File fisik tidak valid atau berupa direktori.');
                    }
                },
            ],
            'file_type' => ['required', 'string'],
            'file_size' => ['nullable', 'numeric'],
            'category_id' => ['required', 'exists:categories,id'],
            'classification_id' => [
                'required',
                'exists:classifications,id',
                function ($attribute, $value, $fail) {
                    $classification = Classification::find($value);
                    $role = Auth::user()->role ?? 'Admin';
                    if ($role === 'Admin' && $classification && $classification->level === 4) {
                        $fail('Anda tidak memiliki izin untuk menyimpan arsip dengan klasifikasi ini.');
                    }
                    if ($classification && $classification->level === 4) {
                        $fileType = strtolower(request()->input('file_type', ''));
                        if (in_array($fileType, ['doc', 'docx', 'xls', 'xlsx'])) {
                            $fail('Dokumen Office (Word/Excel) tidak didukung untuk Tingkat 4. Harap konversi ke PDF.');
                        }
                    }
                },
            ],
            'summary' => ['nullable', 'string'],
            'extracted_text' => ['nullable', 'string'],
            'ai_model' => ['nullable', 'string'],
            'ai_confidence_score' => ['nullable', 'numeric'],
        ]);

        $this->archiveService->storeArchive($validated, Auth::id());

        return redirect()->route('archives.index')->with('success', 'Arsip berhasil disimpan!');
    }

    public function bulkStore(Request $request)
    {
        $this->authorize('create', Archive::class);

        $validated = $request->validate([
            'archives' => ['required', 'array', 'min:1', 'max:5'],
            'archives.*.title' => ['required', 'string', 'max:255'],
            'archives.*.document_number' => ['nullable', 'string', 'max:255'],
            'archives.*.document_date' => ['nullable', 'date'],
            'archives.*.retention_date' => ['nullable', 'date'],
            'archives.*.file_path' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if ((! str_starts_with($value, 'private/archives/') && ! str_starts_with($value, 'temp/archives/')) || str_contains($value, '..')) {
                        $fail('File path tidak valid.');
                    }
                    $fullPath = Storage::path($value);
                    if (! Storage::exists($value) || ! is_file($fullPath)) {
                        $fail('File fisik tidak valid atau berupa direktori.');
                    }
                },
            ],
            'archives.*.file_type' => ['required', 'string'],
            'archives.*.file_size' => ['nullable', 'numeric'],
            'archives.*.category_id' => ['required', 'exists:categories,id'],
            'archives.*.classification_id' => [
                'required',
                'exists:classifications,id',
                function ($attribute, $value, $fail) {
                    $classification = Classification::find($value);
                    $role = Auth::user()->role ?? 'Admin';
                    if ($role === 'Admin' && $classification && $classification->level === 4) {
                        $fail('Anda tidak memiliki izin untuk menyimpan arsip dengan klasifikasi ini.');
                    }

                    preg_match('/archives\.(\d+)\.classification_id/', $attribute, $matches);
                    $index = $matches[1] ?? null;
                    if ($index !== null && $classification && $classification->level === 4) {
                        $fileType = strtolower(request()->input("archives.{$index}.file_type", ''));
                        if (in_array($fileType, ['doc', 'docx', 'xls', 'xlsx'])) {
                            $fail('Dokumen Office (Word/Excel) tidak didukung untuk Tingkat 4. Harap konversi ke PDF.');
                        }
                    }
                },
            ],
            'archives.*.summary' => ['nullable', 'string'],
            'archives.*.extracted_text' => ['nullable', 'string'],
            'archives.*.ai_model' => ['nullable', 'string'],
            'archives.*.ai_confidence_score' => ['nullable', 'numeric'],
        ]);

        $this->archiveService->storeBulkArchives($validated['archives'], Auth::id());

        return response()->json([
            'message' => 'Semua arsip berhasil disimpan!',
        ]);
    }

    public function stream($id)
    {
        $archive = $this->archiveRepository->findById($id);

        if (! $archive) {
            abort(404);
        }

        $this->authorize('view', $archive);

        // Record Audit Log for VIEW
        ArchiveLog::create([
            'archive_id' => $archive->id,
            'user_id' => Auth::id(),
            'action' => 'VIEW',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $filePath = Storage::path($archive->file_path);

        // Fail-safe Watermarking for Level 4
        if ($archive->classification?->level === 4) {
            $ext = strtolower($archive->file_type);
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                $watermarkedPath = $this->watermarkService->applyWatermark(
                    $archive,
                    Auth::user(),
                    request()->ip()
                );

                if ($watermarkedPath === $filePath) {
                    Log::critical("Security Alert: Watermarking failed for Level 4 Archive ID: {$archive->id}");
                    abort(500, 'Dokumen keamanan tinggi tidak dapat dialirkan karena kegagalan sistem proteksi.');
                }

                $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $archive->title.'.'.$archive->file_type);

                return response()->file($watermarkedPath, [
                    'Content-Type' => mime_content_type($watermarkedPath),
                    'Content-Disposition' => $disposition,
                    'X-Content-Type-Options' => 'nosniff',
                    'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self';",
                    'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
                    'Pragma' => 'no-cache',
                    'Cross-Origin-Resource-Policy' => 'same-origin',
                ])->deleteFileAfterSend(true);
            } else {
                Log::critical("Security Alert: Unsupported format {$ext} for Level 4 Archive ID: {$archive->id}");
                abort(500, 'Dokumen keamanan tinggi dengan format ini belum didukung untuk dialirkan dengan proteksi.');
            }
        }

        if (! Storage::exists($archive->file_path)) {
            abort(404, 'File tidak ditemukan di server.');
        }

        $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $archive->title.'.'.$archive->file_type);

        return response()->file($filePath, [
            'Content-Type' => mime_content_type($filePath),
            'Content-Disposition' => $disposition,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self';",
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ]);
    }

    public function download($id)
    {
        $archive = $this->archiveRepository->findById($id);

        if (! $archive) {
            abort(404);
        }

        $this->authorize('view', $archive);

        ArchiveLog::create([
            'archive_id' => $archive->id,
            'user_id' => Auth::id(),
            'action' => 'DOWNLOAD',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        // Apply Security Watermark for Level 4
        if ($archive->classification?->level === 4) {
            $ext = strtolower($archive->file_type);
            if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                $watermarkedPath = $this->watermarkService->applyWatermark(
                    $archive,
                    Auth::user(),
                    request()->ip()
                );

                if ($watermarkedPath === Storage::path($archive->file_path)) {
                    Log::critical("Security Alert: Watermarking failed for Level 4 Archive ID: {$archive->id} on download");
                    abort(500, 'Dokumen keamanan tinggi tidak dapat diunduh karena kegagalan sistem proteksi.');
                }

                $disposition = HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $archive->title.'.'.$archive->file_type);

                return response()->file($watermarkedPath, [
                    'Content-Type' => mime_content_type($watermarkedPath),
                    'Content-Disposition' => $disposition,
                ])->deleteFileAfterSend(true);
            } else {
                Log::critical("Security Alert: Unsupported format {$ext} for Level 4 Archive ID: {$archive->id}");
                abort(500, 'Dokumen keamanan tinggi dengan format ini belum didukung untuk diunduh dengan proteksi.');
            }
        }

        return Storage::download($archive->file_path, $archive->title.'.'.$archive->file_type);
    }

    public function preview($id)
    {
        $archive = $this->archiveRepository->findById($id);

        if (! $archive) {
            abort(404);
        }

        $this->authorize('view', $archive);

        return view('pages.archives.preview', compact('archive'));
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
        $this->authorize('viewAny', Archive::class);
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
        $this->authorize('viewAny', Archive::class);
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
