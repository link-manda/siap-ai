@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            Manajemen Arsip
        </h2>
        <a href="{{ route('archives.create') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 py-3 px-6 text-center font-medium text-white hover:bg-opacity-90">
            Upload Arsip Baru
        </a>
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
                                    {{ $archive->classification->name ?? '-' }}
                                </p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $archive->document_date ? $archive->document_date->format('d M Y') : '-' }}</p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <a href="{{ route('archives.download', $archive->id) }}" class="text-brand-500 hover:text-brand-600 text-sm">Download</a>
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
