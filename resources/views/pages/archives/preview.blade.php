@extends('layouts.app')

@section('content')
<div class="rounded-sm border border-stroke bg-white px-5 pt-6 pb-2.5 shadow-default dark:border-strokedark dark:bg-boxdark sm:px-7.5 xl:pb-1 relative">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h4 class="text-xl font-bold text-black dark:text-white">
                Pratinjau Arsip: {{ $archive->title }}
            </h4>
            <p class="text-sm text-gray-500">Format: {{ strtoupper($archive->file_type) }}</p>
        </div>
        <a href="{{ route('archives.index') }}" class="inline-flex items-center justify-center rounded-md bg-gray-500 py-2 px-4 text-center font-medium text-white hover:bg-opacity-90">
            Kembali
        </a>
    </div>

    <!-- Container Utama Preview -->
    <div class="relative w-full border bg-gray-100 overflow-auto" style="height: 70vh;" id="preview-container">
        <!-- Watermark Overlay (Anti Screenshot) -->
        <div class="absolute inset-0 pointer-events-none z-50 flex items-center justify-center opacity-30 overflow-hidden">
            <div class="w-[200%] h-[200%] flex flex-wrap -rotate-45 items-center justify-center pointer-events-none" style="gap: 50px;">
                @for($i=0; $i<50; $i++)
                    <span class="text-2xl font-bold text-gray-400 select-none">
                        Dilihat oleh {{ Auth::user()->name }}<br>{{ request()->ip() }}
                    </span>
                @endfor
            </div>
        </div>

        <!-- Render Content Here -->
        <div id="render-target" class="w-full h-full bg-white relative z-10 flex justify-center p-4">
            <div class="flex items-center justify-center h-full w-full" id="loading-spinner">
                <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const fileType = @json(strtolower($archive->file_type));
        const streamUrl = @json(route('archives.stream', $archive->id));
        const target = document.getElementById("render-target");
        const loading = document.getElementById("loading-spinner");

        if (['pdf', 'jpg', 'jpeg', 'png'].includes(fileType)) {
            // Native render for PDF & Images
            let element = '';
            if(fileType === 'pdf') {
                element = `<iframe src="${streamUrl}" sandbox="allow-scripts" class="w-full h-full border-0"></iframe>`;
            } else {
                element = `<img src="${streamUrl}" class="max-w-full object-contain" />`;
            }
            target.innerHTML = element;
        } 
        else if (['doc', 'docx', 'xls', 'xlsx'].includes(fileType)) {
            // Fetch array buffer for mammoth/xlsx
            fetch(streamUrl)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.arrayBuffer();
                })
                .then(buffer => {
                    loading.style.display = 'none';
                    if (['docx', 'doc'].includes(fileType)) {
                        window.mammoth.convertToHtml({arrayBuffer: buffer})
                            .then(function(result) {
                                const cleanHtml = window.DOMPurify.sanitize(result.value);
                                target.innerHTML = `<div class="text-left w-full max-w-4xl prose">${cleanHtml}</div>`;
                            })
                            .catch(function(err) {
                                target.textContent = 'Error memuat dokumen Word: ' + err.message;
                            });
                    } else if (['xlsx', 'xls'].includes(fileType)) {
                        const workbook = window.XLSX.read(buffer, {type: 'array'});
                        const firstSheet = workbook.SheetNames[0];
                        const htmlstr = window.XLSX.utils.sheet_to_html(workbook.Sheets[firstSheet], { id: "excel-table" });
                        
                        // Add some tailwind classes to the generated table string
                        let styledHtml = htmlstr.replace('<table', '<table class="w-full table-auto border-collapse border border-gray-300"');
                        styledHtml = styledHtml.replace(/<td/g, '<td class="border border-gray-300 px-4 py-2"');
                        
                        const cleanExcelHtml = window.DOMPurify.sanitize(styledHtml);
                        target.innerHTML = `<div class="overflow-x-auto w-full">${cleanExcelHtml}</div>`;
                    }
                })
                .catch(err => {
                    target.textContent = 'Error mengambil dokumen: ' + err.message;
                });
        } else {
            target.textContent = 'Pratinjau tidak didukung untuk format ini.';
        }
    });
</script>
@endpush
