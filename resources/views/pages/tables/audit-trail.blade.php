@extends('layouts.app')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            Log Aktivitas (Audit Trail)
        </h2>
    </div>
    
    <div x-data="{ activeTab: 'dokumen' }" class="space-y-6">
        <!-- Tabs -->
        <div class="border-b border-gray-200 dark:border-gray-800">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">
                <a @click.prevent="activeTab = 'dokumen'" 
                   :class="activeTab === 'dokumen' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200'"
                   href="#" 
                   class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                    Aktivitas Dokumen
                </a>
                <a @click.prevent="activeTab = 'sistem'"
                   :class="activeTab === 'sistem' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200'"
                   href="#" 
                   class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium">
                    Aktivitas Sistem
                </a>
            </nav>
        </div>

        <!-- Dokumen Tab -->
        <div x-show="activeTab === 'dokumen'" class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Waktu</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Pengguna</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Aksi</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Arsip Terkait</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">IP Address</p></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $log->created_at->format('d M Y, H:i') }}</p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-800 font-medium text-theme-sm dark:text-white">{{ $log->user->name ?? 'System' }}</p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-theme-xs inline-block rounded-full px-2 py-0.5 font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                    {{ $log->action }}
                                </p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $log->archive->title ?? 'Arsip Dihapus' }}</p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $log->ip_address }}</p>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-4 sm:px-6 text-center text-gray-500 text-sm">Belum ada aktivitas dokumen.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                {{ $logs->links() }}
            </div>
        </div>

        <!-- Sistem Tab -->
        <div x-show="activeTab === 'sistem'" x-cloak class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900">
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Waktu</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Pengguna</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Event</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Model Terkait</p></th>
                            <th class="px-5 py-3 text-start sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Keterangan (JSON)</p></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($systemLogs as $systemLog)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $systemLog->created_at->format('d M Y, H:i') }}</p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-800 font-medium text-theme-sm dark:text-white">{{ $systemLog->causer->name ?? 'System' }}</p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-theme-xs inline-block rounded-full px-2 py-0.5 font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                    {{ $systemLog->event }}
                                </p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ class_basename($systemLog->subject_type) }} #{{ $systemLog->subject_id }}</p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <pre class="text-xs text-gray-500 dark:text-gray-400 max-w-xs overflow-x-auto">{{ json_encode($systemLog->properties, JSON_PRETTY_PRINT) }}</pre>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-4 sm:px-6 text-center text-gray-500 text-sm">Belum ada aktivitas sistem.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
                {{ $systemLogs->links() }}
            </div>
        </div>
    </div>
@endsection
