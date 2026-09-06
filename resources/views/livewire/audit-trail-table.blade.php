<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Log Aktivitas (Audit Trail)</h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Rekam jejak akses dokumen dan perubahan data dalam sistem SIAP-AI.</p>
        </div>

        {{-- Search input --}}
        <div class="w-full sm:w-72">
            <div class="relative">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari aktivitas atau pengguna..." 
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-hidden focus:ring-1 focus:ring-indigo-500" />
                <div wire:loading.inline wire:target="search" class="absolute right-2.5 top-2.5">
                    <svg class="animate-spin h-3.5 w-3.5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-zinc-200 dark:border-zinc-800">
        <nav class="-mb-px flex gap-6" aria-label="Tabs">
            <button 
                type="button"
                wire:click="setTab('document')" 
                class="whitespace-nowrap border-b-2 py-3 px-1 text-xs font-semibold transition {{ $tab === 'document' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                Aktivitas Dokumen (Akses & Unduh)
            </button>
            <button 
                type="button"
                wire:click="setTab('system')" 
                class="whitespace-nowrap border-b-2 py-3 px-1 text-xs font-semibold transition {{ $tab === 'system' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-200' }}">
                Aktivitas Sistem (Perubahan Data CRUD)
            </button>
        </nav>
    </div>

    {{-- Content Table --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-xs">
        @if ($tab === 'document')
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/75 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Waktu</th>
                            <th class="py-3 px-4">Pengguna</th>
                            <th class="py-3 px-4">Aksi</th>
                            <th class="py-3 px-4">Arsip Terkait</th>
                            <th class="py-3 px-4">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        @forelse ($documentLogs as $log)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition">
                                <td class="py-3 px-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400">
                                    {{ $log->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="py-3 px-4 font-medium text-zinc-800 dark:text-white">
                                    {{ $log->user->name ?? 'System' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-zinc-600 dark:text-zinc-300">
                                    {{ $log->archive->title ?? 'Arsip Dihapus' }}
                                </td>
                                <td class="py-3 px-4 text-zinc-400 font-mono text-[11px]">
                                    {{ $log->ip_address }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-zinc-400 dark:text-zinc-500">
                                    Belum ada catatan aktivitas dokumen.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($documentLogs->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-850">
                    {{ $documentLogs->links() }}
                </div>
            @endif
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/75 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 font-semibold uppercase tracking-wider">
                            <th class="py-3 px-4">Waktu</th>
                            <th class="py-3 px-4">Pengguna</th>
                            <th class="py-3 px-4">Event</th>
                            <th class="py-3 px-4">Model Terkait</th>
                            <th class="py-3 px-4">Detail Perubahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        @forelse ($systemLogs as $sLog)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition">
                                <td class="py-3 px-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400">
                                    {{ $sLog->created_at->format('d M Y, H:i') }}
                                </td>
                                <td class="py-3 px-4 font-medium text-zinc-800 dark:text-white">
                                    {{ $sLog->causer->name ?? 'System' }}
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $eventColor = match($sLog->event) {
                                            'created' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
                                            'updated' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border-blue-200 dark:border-blue-800',
                                            'deleted' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border-rose-200 dark:border-rose-800',
                                            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium border {{ $eventColor }}">
                                        {{ $sLog->event }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-zinc-600 dark:text-zinc-300">
                                    {{ class_basename($sLog->subject_type) }} #{{ $sLog->subject_id }}
                                </td>
                                <td class="py-3 px-4 text-zinc-500 max-w-xs">
                                    <pre class="text-[10px] text-zinc-500 dark:text-zinc-400 font-mono overflow-x-auto max-h-16 p-1.5 rounded-sm bg-zinc-50 dark:bg-zinc-800/80">{{ json_encode($sLog->properties, JSON_PRETTY_PRINT) }}</pre>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-zinc-400 dark:text-zinc-500">
                                    Belum ada catatan aktivitas sistem.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($systemLogs->hasPages())
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-850">
                    {{ $systemLogs->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
