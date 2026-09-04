<?php

namespace App\Http\Controllers;

use App\Interfaces\ArchiveRepositoryInterface;
use App\Services\ArchiveService;
use App\Models\Category;
use App\Models\Classification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ArchiveController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ArchiveRepositoryInterface $archiveRepository,
        protected ArchiveService $archiveService
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'category_id' => $request->get('category_id'),
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
            'extracted_text' => ['nullable', 'string']
        ]);

        $aiDraftData = $this->archiveService->processUpload(
            $request->file('document'),
            $request->input('extracted_text')
        );

        return response()->json([
            'status' => 'success',
            'data' => $aiDraftData
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'document_number' => ['nullable', 'string', 'max:255'],
            'document_date' => ['nullable', 'date'],
            'file_path' => ['required', 'string'],
            'file_type' => ['required', 'string'],
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

        if (!$archive) {
            abort(404);
        }
        
        $this->authorize('view', $archive);

        // Observer will catch this download via a custom log if we update it, 
        // but for now we manually log or let a dedicated route handle it.
        // Actually, we should manually log the download action since Observer only handles Eloquent events.
        
        \App\Models\ArchiveLog::create([
            'archive_id' => $archive->id,
            'user_id' => Auth::id(),
            'action' => 'DOWNLOAD',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return Storage::download($archive->file_path, $archive->title . '.' . $archive->file_type);
    }

    public function destroy($id)
    {
        $archive = $this->archiveRepository->findById($id);
        
        if (!$archive) {
            abort(404);
        }

        $this->authorize('delete', $archive);

        $archive->delete(); // This triggers the observer's "deleted" event for soft deletes

        return redirect()->route('archives.index')->with('success', 'Arsip berhasil dihapus!');
    }
}
