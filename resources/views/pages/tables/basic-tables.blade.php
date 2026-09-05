@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            Manajemen Arsip
        </h2>
        <div class="flex gap-2">
            <a href="{{ route('archives.export.excel', request()->all()) }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-green-500 py-3 px-6 text-center font-medium text-white hover:bg-opacity-90">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export Excel
            </a>
            <a href="{{ route('archives.export.pdf', request()->all()) }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-red-500 py-3 px-6 text-center font-medium text-white hover:bg-opacity-90">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Export PDF
            </a>
            <a href="{{ route('archives.create') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 py-3 px-6 text-center font-medium text-white hover:bg-opacity-90">
                Upload Arsip Baru
            </a>
        </div>
    </div>
    
    <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('archives.index') }}" class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="w-full sm:w-1/4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Kategori</label>
                <select name="category_id" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-gray-800 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:text-white">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-1/4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Level Kerahasiaan</label>
                <select name="classification_id" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-gray-800 outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:text-white">
                    <option value="">Semua Level</option>
                    @php
                        $userClassifications = Auth::user()->role === 'Pimpinan' ? \App\Models\Classification::all() : \App\Models\Classification::where('level', '<=', 3)->get();
                    @endphp
                    @foreach($userClassifications as $classification)
                        <option value="{{ $classification->id }}" {{ request('classification_id') == $classification->id ? 'selected' : '' }}>{{ $classification->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-1/4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white">
            </div>
            <div class="w-full sm:w-1/4">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:text-white">
            </div>
            <div class="w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto rounded-md bg-gray-800 py-2.5 px-6 font-medium text-white hover:bg-gray-700 dark:bg-white dark:text-gray-800 dark:hover:bg-gray-100">
                    Filter
                </button>
            </div>
        </form>
    </div>
    
    <div class="space-y-6">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Judul / No. Dokumen</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Kategori</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Klasifikasi</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Tanggal Dokumen</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Aksi</p></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($archives as $archive)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-5 py-4 sm:px-6">
                                <span class="block font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $archive->title }}</span>
                                <span class="block text-gray-500 text-theme-xs dark:text-gray-400">{{ $archive->document_number ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $archive->category->name ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-theme-xs inline-block rounded-full px-2 py-0.5 font-medium bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-500">
                                    {{ $archive->classification?->name ?? '-' }}
                                </p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $archive->document_date ? $archive->document_date->format('d M Y') : '-' }}</p>
                            </td>
                            <td class="px-5 py-4 sm:px-6 flex space-x-3">
                                <a href="{{ route('archives.preview', $archive->id) }}" class="text-blue-500 hover:text-blue-600 text-sm flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    Preview
                                </a>
                                <a href="{{ route('archives.download', $archive->id) }}" class="text-brand-500 hover:text-brand-600 text-sm flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    Download
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-4 sm:px-6 text-center text-gray-500 text-sm">Belum ada arsip.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                {{ $archives->links() }}
            </div>
        </div>
    </div>
@endsection
