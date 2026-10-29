<?php

namespace App\Http\Controllers;

use App\Models\Archive;
use App\Models\ArchiveAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;

class ArchiveAttachmentController extends Controller
{
    public function store(Request $request, Archive $archive)
    {
        Gate::authorize('create', Archive::class);

        $request->validate([
            'attachments' => 'required|array',
            'attachments.*' => 'required|file|max:20480', // 20MB max
        ]);

        foreach ($request->file('attachments') as $file) {
            $path = $file->store('private/attachments');

            $archive->attachments()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_type' => $file->extension() ?: $file->getClientOriginalExtension(),
            ]);
        }

        return back()->with('success', 'Lampiran berhasil ditambahkan.');
    }

    public function download(ArchiveAttachment $attachment)
    {
        Gate::authorize('view', $attachment->archive);

        if (! Storage::exists($attachment->file_path)) {
            abort(404, 'File lampiran tidak ditemukan.');
        }

        return Storage::download($attachment->file_path, $attachment->file_name);
    }

    public function stream(ArchiveAttachment $attachment)
    {
        Gate::authorize('view', $attachment->archive);

        if (! Storage::exists($attachment->file_path)) {
            abort(404, 'File lampiran tidak ditemukan.');
        }

        $filePath = Storage::path($attachment->file_path);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            $attachment->file_name
        );

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

    public function destroy(ArchiveAttachment $attachment)
    {
        Gate::authorize('delete', $attachment->archive);

        $attachment->delete();

        return back()->with('success', 'Lampiran berhasil dihapus.');
    }
}
