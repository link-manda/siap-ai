@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Unggah Arsip Massal (AI)</h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Ekstrak teks, ringkasan, dan klasifikasi otomatis hingga 5 dokumen sekaligus menggunakan AI.</p>
        </div>
    </div>

    <div x-data="bulkUpload()" class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <!-- Panel Kiri: Daftar Antrean & Upload -->
        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs">
                <div class="border-b border-zinc-200 dark:border-zinc-800 px-6 py-4">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">1. Pilih Dokumen (Maksimal 5)</h3>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <label class="mb-2 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Pilih Berkas (PDF, Word, Excel, Gambar)
                        </label>
                        <input
                            type="file"
                            multiple
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                            @change="handleFiles($event.target.files)"
                            class="w-full text-xs cursor-pointer rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 file:mr-4 file:py-2.5 file:px-4 file:rounded-l-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white hover:file:bg-emerald-500 text-zinc-700 dark:text-zinc-300 focus:outline-hidden"
                            :disabled="files.length >= 5 || isSaving"
                        />
                        <p class="mt-2 text-xs text-amber-600 dark:text-amber-400" x-show="files.length >= 5" x-cloak>Batas maksimal 5 berkas telah tercapai.</p>
                    </div>

                    <!-- Daftar File -->
                    <div class="space-y-3 mt-6">
                        <template x-for="(fileItem, index) in files" :key="fileItem.id">
                            <div
                                @click="activeIndex = index"
                                class="flex flex-col gap-2 rounded-lg border p-4 cursor-pointer transition"
                                :class="{'border-emerald-500 bg-brand-50 dark:bg-brand-500/10': activeIndex === index, 'border-gray-200 dark:border-gray-700': activeIndex !== index}"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <div class="flex-shrink-0">
                                            <!-- Icon -->
                                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="fileItem.file.name"></p>
                                            <p class="text-xs text-gray-500 truncate">
                                                <span x-text="(fileItem.file.size / 1024).toFixed(2) + ' KB'"></span>
                                                <span class="mx-1">•</span>
                                                <!-- Status -->
                                                <span x-show="fileItem.status === 'pending'" class="text-gray-500">Menunggu...</span>
                                                <span x-show="fileItem.status === 'processing'" class="text-emerald-500 font-medium">
                                                    <svg class="animate-spin inline-block h-3 w-3 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="4" stroke-dasharray="32" stroke-dashoffset="16"></circle></svg> AI Menganalisis...
                                                </span>
                                                <span x-show="fileItem.status === 'success'" class="text-success-500 font-medium">Selesai</span>
                                                <span x-show="fileItem.status === 'warning'" class="text-amber-500 font-medium" x-text="fileItem.error || 'Perlu Diisi Manual'"></span>
                                                <span x-show="fileItem.status === 'error'" class="text-error-500 font-medium" x-text="fileItem.error"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <button type="button" @click.stop="openPreview(fileItem)" class="p-1.5 text-gray-500 hover:text-emerald-500 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-800" title="Preview">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>
                                        <button type="button" @click.stop="removeFile(index)" class="p-1.5 text-gray-500 hover:text-error-500 hover:bg-gray-100 rounded-lg dark:hover:bg-gray-800" title="Batal">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="files.length === 0" x-cloak class="text-center py-6 text-sm text-gray-500 border border-dashed rounded-lg border-gray-300 dark:border-gray-700">
                            Belum ada file yang dipilih.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Kanan: Form Edit -->
        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs h-full flex flex-col">
                <div class="border-b border-zinc-200 dark:border-zinc-800 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">2. Edit Detail File</h3>
                    <span x-show="files.length > 0" x-cloak class="text-xs font-medium px-2 py-0.5 bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 rounded-full" x-text="`File ${activeIndex + 1} dari ${files.length}`"></span>
                </div>

                <div class="p-6 flex-grow">
                    <template x-if="files.length > 0 && activeFile">
                        <div class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-zinc-700 dark:text-zinc-300">Judul Arsip <span class="text-rose-500">*</span></label>
                                <input type="text" x-model="activeFile.data.title" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-emerald-500" :disabled="isSaving" />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-zinc-700 dark:text-zinc-300">Nomor Dokumen</label>
                                <input type="text" x-model="activeFile.data.document_number" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-emerald-500" :disabled="isSaving" />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-zinc-700 dark:text-zinc-300">Kategori Prediksi AI <span class="text-rose-500">*</span></label>
                                <select x-model="activeFile.data.category_id" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-emerald-500" :disabled="isSaving">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-zinc-700 dark:text-zinc-300">Klasifikasi Keamanan <span class="text-rose-500">*</span></label>
                                <select x-model="activeFile.data.classification_id" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-emerald-500" :disabled="isSaving">
                                    <option value="">-- Pilih Klasifikasi --</option>
                                    @foreach($classifications as $classification)
                                        @if(!auth()->user()->hasRole('Pimpinan') && $classification->level === 4)
                                            @continue
                                        @endif
                                        <option value="{{ $classification->id }}">{{ $classification->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-zinc-700 dark:text-zinc-300">Ringkasan AI</label>
                                <textarea x-model="activeFile.data.summary" rows="4" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-emerald-500" :disabled="isSaving"></textarea>
                            </div>
                        </div>
                    </template>

                    <div x-show="files.length === 0" x-cloak class="h-full flex items-center justify-center text-sm text-gray-400">
                        Pilih file di panel kiri untuk diedit.
                    </div>
                </div>

                <div class="p-6 border-t border-gray-200 dark:border-gray-800" x-show="files.length > 0" x-cloak>
                    <div x-show="globalError" x-cloak class="mb-4 p-3 bg-error-50 text-error-700 rounded-lg text-sm" x-text="globalError"></div>
                    <button type="button" @click="saveAll()" :disabled="isSaving || hasProcessingFiles()" class="flex w-full items-center justify-center rounded-lg bg-emerald-500 py-2.5 px-4 font-medium text-white hover:bg-emerald-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg x-show="isSaving" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="isSaving ? 'Menyimpan Semua...' : 'Simpan Semua Arsip'"></span>
                    </button>
                    <p class="mt-2 text-xs text-center text-gray-500" x-show="hasProcessingFiles()" x-cloak>Tunggu hingga semua proses AI selesai.</p>
                </div>
            </div>
        </div>

        <!-- Quick Preview Modal -->
        <div x-show="previewModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" style="display: none;">
            <div @click.away="previewModalOpen = false" class="relative w-full max-w-4xl rounded-xl bg-white shadow-xl dark:bg-gray-900 flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-800 flex-shrink-0">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white" x-text="'Preview: ' + (previewFile ? previewFile.file.name : '')"></h3>
                    <button @click="previewModalOpen = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-6 overflow-auto bg-gray-50 dark:bg-gray-800 flex-grow" id="preview-container">
                    <!-- Content injected by JS -->
                    <div x-show="previewLoading" class="flex justify-center items-center py-12">
                        <svg class="animate-spin h-8 w-8 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="4" stroke-dasharray="32" stroke-dashoffset="16"></circle></svg>
                    </div>
                    <div id="preview-content" x-show="!previewLoading" class="w-full h-full min-h-[500px]"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const processAiUrl = @json(route('archives.process-ai'));
    const bulkStoreUrl = @json(route('archives.bulk-store'));
    const csrfToken = @json(csrf_token());
    const redirectUrl = @json(route('archives.index'));

    function bulkUpload() {
        return {
            files: [],
            activeIndex: 0,
            isSaving: false,
            globalError: null,
            previewModalOpen: false,
            previewFile: null,
            previewLoading: false,

            get activeFile() {
                return this.files[this.activeIndex] || null;
            },

            hasProcessingFiles() {
                return this.files.some(f => f.status === 'pending' || f.status === 'processing');
            },

            hasErrorFiles() {
                return this.files.some(f => f.status === 'error');
            },

            handleFiles(selectedFiles) {
                this.globalError = null;
                const newFiles = Array.from(selectedFiles);

                for (let file of newFiles) {
                    if (this.files.length >= 5) break;

                    const id = Math.random().toString(36).substring(7);

                    // Validasi batas ukuran 10MB (10.485.760 bytes)
                    const isOversize = file.size > 10 * 1024 * 1024;

                    const fileItem = {
                        id: id,
                        file: file,
                        status: isOversize ? 'error' : 'pending',
                        error: isOversize ? 'Gagal: Ukuran berkas melebihi 10MB' : null,
                        data: {
                            title: file.name.split('.').slice(0, -1).join('.'),
                            document_number: '',
                            category_id: '',
                            classification_id: '',
                            summary: '',
                            file_path: '',
                            file_type: '',
                            file_size: '',
                            extracted_text: '',
                            ai_model: '',
                            ai_confidence_score: ''
                        }
                    };

                    this.files.push(fileItem);

                    // Hanya proses ke AI jika status bukan error
                    if (!isOversize) {
                        this.processSingleFile(this.files[this.files.length - 1]);
                    }
                }

                if (this.files.length > 0 && this.activeIndex >= this.files.length) {
                    this.activeIndex = 0;
                }
            },

            removeFile(index) {
                const fileItem = this.files[index];
                if (fileItem && fileItem.previewUrl) {
                    URL.revokeObjectURL(fileItem.previewUrl);
                }
                this.files.splice(index, 1);
                if (this.activeIndex >= this.files.length) {
                    this.activeIndex = Math.max(0, this.files.length - 1);
                }
            },

            async processSingleFile(fileItem) {
                fileItem.status = 'processing';

                const formData = new FormData();
                formData.append('document', fileItem.file);

                try {
                    const response = await fetch(processAiUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    if (response.status === 419) {
                        throw new Error("Sesi Anda telah kedaluwarsa. Silakan muat ulang halaman.");
                    }
                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("Respons server bukan JSON (Terjadi galat 500).");
                    }

                    const result = await response.json();

                    if (response.ok) {
                        fileItem.status = result.data.is_ai_degraded ? 'warning' : 'success';
                        if (result.data.is_ai_degraded) {
                            fileItem.error = 'Analisis AI terbatas (Harap verifikasi)';
                        }
                        fileItem.data.file_path = result.data.file_path;
                        fileItem.data.file_type = result.data.file_type;
                        fileItem.data.file_size = result.data.file_size;
                        fileItem.data.extracted_text = result.data.extracted_text;
                        fileItem.data.title = result.data.title;
                        fileItem.data.category_id = result.data.category_id;
                        fileItem.data.classification_id = result.data.classification_id;
                        fileItem.data.summary = result.data.summary;
                        fileItem.data.ai_model = result.data.ai_model;
                        fileItem.data.ai_confidence_score = result.data.ai_confidence_score;
                    } else {
                        throw new Error(result.message || 'Terjadi kesalahan');
                    }
                } catch (error) {
                    fileItem.status = 'error';
                    fileItem.error = error.message;
                }
            },

            async saveAll() {
                if (this.files.length === 0) return;

                // Cek jika ada berkas yang berstatus error (misal karena ukuran > 10MB)
                if (this.hasErrorFiles()) {
                    this.globalError = 'Terdapat berkas yang bermasalah (warna merah). Harap hapus berkas tersebut dari daftar sebelum menyimpan.';
                    return;
                }

                // Validate
                for (let i = 0; i < this.files.length; i++) {
                    const f = this.files[i].data;
                    if (!f.title || !f.category_id || !f.classification_id) {
                        this.globalError = `File ${i+1} memiliki isian wajib yang kosong.`;
                        this.activeIndex = i;
                        return;
                    }
                }

                this.isSaving = true;
                this.globalError = null;

                const payload = {
                    archives: this.files.map(f => f.data)
                };

                try {
                    const response = await fetch(bulkStoreUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (response.status === 419) {
                        throw new Error("Sesi Anda telah kedaluwarsa. Silakan muat ulang halaman.");
                    }
                    const contentType = response.headers.get("content-type");
                    if (!contentType || !contentType.includes("application/json")) {
                        throw new Error("Respons server bukan JSON (Terjadi galat 500).");
                    }

                    const result = await response.json();

                    if (response.ok) {
                        window.location.href = redirectUrl;
                    } else {
                        let errMsg = result.message || 'Terjadi kesalahan saat menyimpan.';
                        if (result.errors) {
                            const firstErr = Object.values(result.errors)[0];
                            errMsg += ' ' + (Array.isArray(firstErr) ? firstErr[0] : firstErr);
                        }
                        throw new Error(errMsg);
                    }
                } catch (error) {
                    this.globalError = error.message;
                    this.isSaving = false;
                }
            },

            async openPreview(fileItem) {
                this.previewFile = fileItem;
                this.previewModalOpen = true;
                this.previewLoading = true;

                const container = document.getElementById('preview-content');
                container.innerHTML = '';
                container.className = 'w-full h-full min-h-[500px]'; // reset class

                const file = fileItem.file;
                const ext = file.name.split('.').pop().toLowerCase();

                try {
                    if (!window.DOMPurify) {
                        throw new Error('Pustaka keamanan DOMPurify tidak termuat (Fail-Closed).');
                    }

                    if (['pdf', 'jpg', 'jpeg', 'png'].includes(ext)) {
                        if (!fileItem.previewUrl) {
                            fileItem.previewUrl = URL.createObjectURL(file);
                        }
                        const url = fileItem.previewUrl;
                        if (ext === 'pdf') {
                            container.innerHTML = `<div class="relative w-full h-[600px]"><iframe src="${url}" class="w-full h-full border-0 rounded-lg shadow-sm"></iframe><div class="absolute inset-0 pointer-events-none flex items-center justify-center opacity-10"><span class="text-6xl font-bold rotate-45 text-black">CONFIDENTIAL</span></div></div>`;
                        } else {
                            container.innerHTML = `<div class="relative inline-block w-full text-center"><img src="${url}" class="max-w-full h-auto mx-auto rounded-lg shadow-sm" /><div class="absolute inset-0 pointer-events-none flex items-center justify-center opacity-20"><span class="text-4xl font-bold rotate-45 text-white bg-black/30 px-4 py-2 rounded">CONFIDENTIAL</span></div></div>`;
                        }
                    } else if (['doc', 'docx'].includes(ext)) {
                        const arrayBuffer = await file.arrayBuffer();
                        if (window.mammoth) {
                            const result = await window.mammoth.convertToHtml({arrayBuffer: arrayBuffer});
                            let html = window.DOMPurify.sanitize(result.value);
                            container.innerHTML = `<div class="bg-white p-8 rounded-lg shadow-sm prose max-w-none dark:bg-gray-900 dark:prose-invert relative"><div class="absolute inset-0 pointer-events-none flex items-center justify-center opacity-10"><span class="text-6xl font-bold rotate-45 text-black">CONFIDENTIAL</span></div>${html}</div>`;
                        } else {
                            container.innerText = 'Pustaka Mammoth.js tidak termuat.';
                        }
                    } else if (['xls', 'xlsx'].includes(ext)) {
                        const arrayBuffer = await file.arrayBuffer();
                        if (window.XLSX) {
                            const workbook = window.XLSX.read(arrayBuffer, {type: 'array'});
                            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                            let html = window.DOMPurify.sanitize(window.XLSX.utils.sheet_to_html(firstSheet, { id: 'excel-table' }));
                            container.innerHTML = `<div class="bg-white p-4 rounded-lg shadow-sm overflow-x-auto dark:bg-gray-900 excel-preview relative"><div class="absolute inset-0 pointer-events-none flex items-center justify-center opacity-10"><span class="text-6xl font-bold rotate-45 text-black">CONFIDENTIAL</span></div>${html}</div>`;
                        } else {
                            container.innerText = 'Pustaka SheetJS tidak termuat.';
                        }
                    } else {
                        container.innerText = 'Format file tidak didukung untuk pratinjau cepat.';
                        container.className = "text-gray-500 text-center p-4";
                    }
                } catch (e) {
                    container.innerText = `Gagal merender pratinjau: ${e.message}`;
                    container.className = "text-error-500 text-center p-4 font-medium";
                } finally {
                    this.previewLoading = false;
                }
            }
        }
    }
</script>

<style>
    .excel-preview table {
        width: 100%;
        border-collapse: collapse;
    }
    .excel-preview th, .excel-preview td {
        border: 1px solid #e5e7eb;
        padding: 0.5rem;
        font-size: 0.875rem;
    }
    .dark .excel-preview th, .dark .excel-preview td {
        border-color: #374151;
    }
</style>
@endpush
