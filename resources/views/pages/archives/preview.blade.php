@extends('layouts.app')

@section('content')
<div class="space-y-6">
    {{-- Header & Meta Information --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <flux:button href="{{ route('archives.index') }}" variant="subtle" icon="arrow-left" size="sm" class="-ml-2">
                    Kembali
                </flux:button>
                <flux:badge size="sm" color="zinc" class="uppercase">{{ $archive->file_type }}</flux:badge>
                @if($archive->classification)
                    @php
                        $color = match($archive->classification->level) {
                            1 => 'zinc',
                            2 => 'blue',
                            3 => 'amber',
                            4 => 'red',
                            default => 'zinc',
                        };
                    @endphp
                    <flux:badge size="sm" :color="$color">
                        {{ $archive->classification->name }}
                    </flux:badge>
                @endif
                @if($archive->category)
                    <flux:badge size="sm" color="emerald">
                        {{ $archive->category->name }}
                    </flux:badge>
                @endif
            </div>
            <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                {{ $archive->title }}
            </h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                Nomor: <span class="font-mono">{{ $archive->document_number ?? '-' }}</span> &bull;
                Kode: <span class="font-mono">{{ $archive->archive_code ?? '-' }}</span> &bull;
                Diunggah: {{ $archive->created_at->format('d M Y H:i') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            @can('view', $archive)
                <flux:button href="{{ route('archives.download', $archive->id) }}" icon="arrow-down-tray" size="sm" variant="primary">
                    Unduh Dokumen
                </flux:button>
            @endcan
        </div>
    </div>

    {{-- Viewer Container --}}
    <flux:card class="p-0 overflow-hidden relative border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl">
        <!-- Watermark Overlay (Anti Screenshot) -->
        <div class="absolute inset-0 pointer-events-none z-50 flex items-center justify-center opacity-25 overflow-hidden select-none">
            <div class="w-[200%] h-[200%] flex flex-wrap -rotate-45 items-center justify-center pointer-events-none" style="gap: 60px;">
                @for($i=0; $i<40; $i++)
                    <div class="text-center">
                        <span class="text-lg font-bold text-zinc-400 dark:text-zinc-600 block">
                            Dilihat oleh {{ Auth::user()->name }}
                        </span>
                        <span class="text-xs font-mono text-zinc-400 dark:text-zinc-600 block">
                            {{ request()->ip() }} &bull; {{ now()->format('Y-m-d H:i') }}
                        </span>
                    </div>
                @endfor
            </div>
        </div>

        <!-- Render Content Here -->
        <div class="relative w-full bg-zinc-50 dark:bg-zinc-950 overflow-auto" style="height: 75vh;" id="preview-container">
            <div id="render-target" class="w-full h-full relative z-10 flex justify-center p-4">
                <div class="flex flex-col items-center justify-center h-full w-full gap-3" id="loading-spinner">
                    <svg class="animate-spin h-8 w-8 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-xs text-zinc-500 font-medium">Memuat pratinjau dokumen...</p>
                </div>
            </div>
        </div>
    </flux:card>

    {{-- AI Summary / Metadata Accordion if available --}}
    @if($archive->summary || $archive->extracted_text)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($archive->summary)
                <flux:card class="p-4 space-y-2 border border-zinc-200 dark:border-zinc-800 rounded-xl">
                    <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-semibold text-sm">
                        <flux:icon name="sparkles" size="sm" />
                        <span>Ringkasan Eksekutif AI</span>
                    </div>
                    <p class="text-xs text-zinc-600 dark:text-zinc-300 leading-relaxed">
                        {{ $archive->summary }}
                    </p>
                </flux:card>
            @endif

            @if($archive->extracted_text)
                <flux:card class="p-4 space-y-2 border border-zinc-200 dark:border-zinc-800 rounded-xl">
                    <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300 font-semibold text-sm">
                        <flux:icon name="document-text" size="sm" />
                        <span>Teks Terekstrak (OCR/AI)</span>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 font-mono line-clamp-4 leading-relaxed">
                        {{ Str::limit($archive->extracted_text, 300) }}
                    </p>
                </flux:card>
            @endif
        </div>
    @endif

    {{-- Lampiran Pendukung Section --}}
    <flux:card class="p-6 mt-6 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 flex items-center gap-2">
                <flux:icon name="paper-clip" variant="micro" class="size-5" />
                Lampiran Pendukung
            </h3>

            @can('create', App\Models\Archive::class)
                <flux:button size="sm" variant="subtle" x-data x-on:click="$dispatch('modal-show', { name: 'add-attachment-modal' })">
                    Tambah Lampiran
                </flux:button>
            @endcan
        </div>

        @if($archive->attachments->count() > 0)
            <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nama File</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tipe</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Ukuran</th>
                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-950 divide-y divide-zinc-200 dark:divide-zinc-800">
                        @foreach($archive->attachments as $attachment)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100 font-medium">
                                    {{ $attachment->file_name }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400 uppercase">
                                    {{ $attachment->file_type }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ Illuminate\Support\Number::fileSize($attachment->file_size) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button size="sm" variant="ghost" icon="eye" x-data x-on:click="$dispatch('load-attachment-preview', { url: '{{ route('archives.attachments.stream', $attachment->id) }}', type: '{{ strtolower($attachment->file_type) }}', filename: '{{ addslashes($attachment->file_name) }}' }); $flux.modal('preview-attachment-modal').show();">
                                            Pratinjau
                                        </flux:button>
                                        <flux:button href="{{ route('archives.attachments.download', $attachment->id) }}" size="sm" variant="ghost" icon="arrow-down-tray">
                                            Unduh
                                        </flux:button>
                                        @can('delete', $archive)
                                            <flux:button size="sm" variant="ghost" icon="trash" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" x-data x-on:click="$dispatch('modal-show', { name: 'delete-attachment-modal', url: '{{ route('archives.attachments.destroy', $attachment->id) }}', filename: '{{ addslashes($attachment->file_name) }}' })">
                                                Hapus
                                            </flux:button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-6 border border-dashed border-zinc-300 dark:border-zinc-700 rounded-lg">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Belum ada lampiran pendukung.</p>
            </div>
        @endif
    </flux:card>

    @can('create', App\Models\Archive::class)
        <flux:modal name="add-attachment-modal" variant="flyout" class="space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Tambah Lampiran Baru</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Unggah berkas tambahan untuk dokumen ini.</p>
            </div>

            <form action="{{ route('archives.attachments.store', $archive->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <flux:field>
                    <flux:label>Pilih File</flux:label>
                    <flux:input type="file" name="attachments[]" multiple required />
                    <flux:error name="attachments" />
                </flux:field>

                <div class="flex justify-end gap-2 pt-4">
                    <flux:button type="button" x-on:click="$dispatch('modal-close', { name: 'add-attachment-modal' })" variant="ghost">Batal</flux:button>
                    <flux:button type="submit" variant="primary">Unggah</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal name="preview-attachment-modal" class="min-w-[90vw] md:min-w-[60vw] space-y-6">
            <div x-data="{ url: '', type: '', filename: '' }"
                 @load-attachment-preview.window="
                     url = $event.detail.url;
                     type = $event.detail.type;
                     filename = $event.detail.filename;
                     $nextTick(() => {
                         if(typeof window.renderDocument === 'function') {
                             window.renderDocument(url, type, document.getElementById('attachment-render-target'), document.getElementById('attachment-loading-spinner'));
                         }
                     });
                 ">
                <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-4 mb-4">
                    <div class="pr-8">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100" x-text="filename"></h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 uppercase" x-text="type + ' Document'"></p>
                    </div>
                </div>

                <div class="relative w-full bg-zinc-50 dark:bg-zinc-950 overflow-auto border border-zinc-200 dark:border-zinc-800 rounded-xl" style="height: 75vh;">
                    <div id="attachment-render-target" class="w-full h-full relative z-10 flex justify-center p-4">
                        <div class="flex flex-col items-center justify-center h-full w-full gap-3" id="attachment-loading-spinner">
                            <svg class="animate-spin h-8 w-8 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-xs text-zinc-500 font-medium">Memuat pratinjau lampiran...</p>
                        </div>
                    </div>
                </div>
            </div>
        </flux:modal>

        <flux:modal name="delete-attachment-modal" class="min-w-[22rem]">
            <form x-data="{ url: '', filename: '' }" @modal-show.window="if ($event.detail.name === 'delete-attachment-modal') { url = $event.detail.url; filename = $event.detail.filename; }" :action="url" method="POST" class="space-y-6">
                @csrf
                @method('DELETE')

                <div>
                    <flux:heading size="lg">Hapus Lampiran?</flux:heading>
                    <flux:subheading>
                        <p class="mt-1">Apakah Anda yakin ingin menghapus lampiran <strong x-text="filename" class="text-zinc-900 dark:text-zinc-100"></strong>?</p>
                        <p class="mt-2 text-red-600 dark:text-red-400">Tindakan ini tidak dapat dibatalkan.</p>
                    </flux:subheading>
                </div>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button type="button" x-on:click="$dispatch('modal-close', { name: 'delete-attachment-modal' })" variant="ghost">Batal</flux:button>
                    <flux:button type="submit" variant="danger">Hapus</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan
</div>
@endsection

@push('scripts')
<script>
    window.renderDocument = function(url, fileType, target, loading) {
        if (!target) return;

        // Reset state
        target.innerHTML = '';
        if (loading) {
            loading.style.display = 'flex';
            target.appendChild(loading);
        }

        if (['pdf', 'jpg', 'jpeg', 'png'].includes(fileType)) {
            // Native render for PDF & Images
            let element = '';
            if(fileType === 'pdf') {
                element = `<iframe src="${url}" class="w-full h-full border-0"></iframe>`;
            } else {
                element = `<img src="${url}" class="max-w-full object-contain" />`;
            }
            target.innerHTML = element;
        }
        else if (['doc', 'docx', 'xls', 'xlsx'].includes(fileType)) {
            // Fetch array buffer for mammoth/xlsx
            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.arrayBuffer();
                })
                .then(buffer => {
                    if (['docx', 'doc'].includes(fileType)) {
                        if (!window.mammoth || !window.DOMPurify) {
                            target.textContent = 'Pustaka pembaca dokumen tidak termuat.';
                            return;
                        }
                        window.mammoth.convertToHtml({arrayBuffer: buffer})
                            .then(function(result) {
                                const cleanHtml = window.DOMPurify.sanitize(result.value);
                                target.innerHTML = `<div class="text-left w-full max-w-4xl prose dark:prose-invert bg-white dark:bg-zinc-900 p-8 rounded-lg shadow-sm">${cleanHtml}</div>`;
                            })
                            .catch(function(err) {
                                target.textContent = 'Error memuat dokumen Word: ' + err.message;
                            });
                    } else if (['xlsx', 'xls'].includes(fileType)) {
                        if (!window.XLSX || !window.DOMPurify) {
                            target.textContent = 'Pustaka pembaca spreadsheet tidak termuat.';
                            return;
                        }
                        const workbook = window.XLSX.read(buffer, {type: 'array'});
                        const firstSheet = workbook.SheetNames[0];
                        const htmlstr = window.XLSX.utils.sheet_to_html(workbook.Sheets[firstSheet], { id: "excel-table" });

                        // Add styling classes to the generated table string
                        let styledHtml = htmlstr.replace('<table', '<table class="w-full text-xs text-left border-collapse border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-900"');
                        styledHtml = styledHtml.replace(/<td/g, '<td class="border border-zinc-200 dark:border-zinc-700 px-3 py-2 text-zinc-700 dark:text-zinc-300"');
                        styledHtml = styledHtml.replace(/<th/g, '<th class="border border-zinc-200 dark:border-zinc-700 px-3 py-2 bg-zinc-50 dark:bg-zinc-800 font-semibold text-zinc-900 dark:text-zinc-100"');

                        const cleanExcelHtml = window.DOMPurify.sanitize(styledHtml);
                        target.innerHTML = `<div class="overflow-x-auto w-full p-4">${cleanExcelHtml}</div>`;
                    }
                })
                .catch(err => {
                    target.textContent = 'Error mengambil dokumen: ' + err.message;
                });
        } else {
            target.textContent = 'Pratinjau tidak didukung untuk format ini.';
        }
    };

    document.addEventListener("DOMContentLoaded", function() {
        const fileType = @json(strtolower($archive->file_type));
        const streamUrl = @json(route('archives.stream', $archive->id));
        const target = document.getElementById("render-target");
        const loading = document.getElementById("loading-spinner");

        if(typeof window.renderDocument === 'function') {
            window.renderDocument(streamUrl, fileType, target, loading);
        }
    });
</script>
@endpush
