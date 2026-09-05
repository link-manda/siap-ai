@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            Upload Arsip (Otomatisasi AI)
        </h2>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h3 class="font-medium text-gray-800 dark:text-white/90">1. Unggah Dokumen</h3>
                </div>
                <div class="p-6">
                    <form id="ai-upload-form">
                        <div>
                            <label class="mb-3 block text-sm font-medium text-gray-700 dark:text-gray-400">File Dokumen (PDF/Image/Word/Excel) <span class="text-error-500">*</span></label>
                            <input type="file" id="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                                class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:text-white/90" required />
                        </div>
                        <button type="submit" id="btn-process-ai" class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 py-2.5 px-4 font-medium text-white hover:bg-opacity-90">
                            <svg class="hidden animate-spin h-5 w-5 text-white" id="ai-loading-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="ai-btn-text">Proses dengan AI ✨</span>
                        </button>
                    </form>
                    <div id="ai-message" class="mt-4 hidden rounded-lg p-3 text-sm"></div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <h3 class="font-medium text-gray-800 dark:text-white/90">2. Hasil Ekstraksi & Finalisasi</h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('archives.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="file_path" id="file_path" />
                        <input type="hidden" name="file_type" id="file_type" />
                        <input type="hidden" name="extracted_text" id="extracted_text" />
                        <input type="hidden" name="ai_model" id="ai_model" />
                        <input type="hidden" name="ai_confidence_score" id="ai_confidence_score" />

                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Judul Arsip <span class="text-error-500">*</span></label>
                            <input type="text" name="title" id="title" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-brand-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" required />
                        </div>
                        
                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Nomor Dokumen</label>
                            <input type="text" name="document_number" id="document_number" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-brand-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>

                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Kategori Prediksi AI <span class="text-error-500">*</span></label>
                            <select name="category_id" id="category_id" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-brand-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" required>
                                <option value="">-- Pilih Kategori --</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Klasifikasi Keamanan <span class="text-error-500">*</span></label>
                            <select name="classification_id" id="classification_id" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-brand-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white" required>
                                <option value="">-- Pilih Klasifikasi --</option>
                                @foreach($classifications as $classification)
                                    <option value="{{ $classification->id }}">{{ $classification->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-6">
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Ringkasan AI</label>
                            <textarea name="summary" id="summary" rows="4" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:border-brand-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                        </div>

                        <button type="submit" id="btn-save" disabled class="flex w-full items-center justify-center rounded-lg bg-gray-400 py-2.5 px-4 font-medium text-white transition disabled:cursor-not-allowed">
                            Simpan Arsip
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const processAiUrl = @json(route('archives.process-ai'));
    const csrfToken = @json(csrf_token());

    document.getElementById('ai-upload-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const fileInput = document.getElementById('document_file');
        if (fileInput.files.length === 0) return;

        const formData = new FormData();
        formData.append('document', fileInput.files[0]);

        const btnProcess = document.getElementById('btn-process-ai');
        const iconLoading = document.getElementById('ai-loading-icon');
        const btnText = document.getElementById('ai-btn-text');
        const msgDiv = document.getElementById('ai-message');
        const btnSave = document.getElementById('btn-save');

        // Loading state
        btnProcess.disabled = true;
        btnProcess.classList.add('opacity-70');
        iconLoading.classList.remove('hidden');
        btnText.innerText = 'AI Sedang Menganalisis...';
        msgDiv.classList.add('hidden');
        
        try {
            const response = await fetch(processAiUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok) {
                // Populate forms
                document.getElementById('file_path').value = result.data.file_path;
                document.getElementById('file_type').value = result.data.file_type;
                document.getElementById('extracted_text').value = result.data.extracted_text;
                document.getElementById('title').value = result.data.title;
                document.getElementById('category_id').value = result.data.category_id;
                document.getElementById('classification_id').value = result.data.classification_id;
                document.getElementById('summary').value = result.data.summary;
                document.getElementById('ai_model').value = result.data.ai_model;
                document.getElementById('ai_confidence_score').value = result.data.ai_confidence_score;

                // Success message
                msgDiv.innerHTML = `✅ Analisis Berhasil! (Confidence: ${result.data.ai_confidence_score}%)`;
                msgDiv.className = 'mt-4 rounded-lg p-3 text-sm bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-500';
                msgDiv.classList.remove('hidden');

                // Enable save button
                btnSave.disabled = false;
                btnSave.className = 'flex w-full items-center justify-center rounded-lg bg-success-500 py-2.5 px-4 font-medium text-white hover:bg-success-600 transition';
            } else {
                throw new Error(result.message || 'Terjadi kesalahan saat memproses.');
            }
        } catch (error) {
            msgDiv.innerHTML = `❌ Gagal: ${error.message}`;
            msgDiv.className = 'mt-4 rounded-lg p-3 text-sm bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-500';
            msgDiv.classList.remove('hidden');
        } finally {
            btnProcess.disabled = false;
            btnProcess.classList.remove('opacity-70');
            iconLoading.classList.add('hidden');
            btnText.innerText = 'Proses Ulang dengan AI ✨';
        }
    });
</script>
@endpush
