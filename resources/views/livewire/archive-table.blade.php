<div class="space-y-6">
    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Manajemen Arsip</h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Kelola, cari, dan tinjau seluruh dokumen arsip digital Puskesmas.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @role('Pimpinan')
                @if(count($selected) > 0)
                <button type="button" wire:click="confirmDelete()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/50 transition mr-2">
                    <flux:icon icon="trash" class="size-3.5" />
                    <span>Hapus Massal ({{ count($selected) }})</span>
                </button>
                @endif
                <a href="{{ route('archives.trash') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                    <flux:icon icon="trash" class="size-3.5" />
                    <span>Tong Sampah</span>
                </a>
            @endrole
            <a href="{{ route('archives.export.excel') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition">
                <flux:icon icon="document-text" class="size-3.5" />
                <span>Export Excel</span>
            </a>
            <a href="{{ route('archives.export.pdf') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/50 transition">
                <flux:icon icon="document-text" class="size-3.5" />
                <span>Export PDF</span>
            </a>
            <a href="{{ route('archives.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-sm transition">
                <flux:icon icon="plus" class="size-3.5" />
                <span>Upload Arsip Baru</span>
            </a>
        </div>
    </div>

    {{-- Filter & Search Box --}}
    <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Pencarian</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-zinc-400">
                        <flux:icon icon="magnifying-glass" class="size-3.5" />
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search" 
                        placeholder="Cari judul atau nomor..." 
                        class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 pl-9 pr-3 py-2 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                    >
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Kategori</label>
                <select 
                    wire:model.live="categoryId" 
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Level Kerahasiaan</label>
                <select 
                    wire:model.live="classificationId" 
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500">
                    <option value="">Semua Tingkat</option>
                    @foreach ($classifications as $cls)
                        <option value="{{ $cls->id }}">{{ $cls->name }} (Level {{ $cls->level }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Tampilkan per halaman</label>
                <select 
                    wire:model.live="perPage" 
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500">
                    <option value="10">10 data</option>
                    <option value="25">25 data</option>
                    <option value="50">50 data</option>
                </select>
            </div>
        </div>
    </div>

    {{-- DataTable Area --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/75 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 font-semibold uppercase tracking-wider">
                        @role('Pimpinan')
                        <th class="py-3 px-4 w-10">
                            <input type="checkbox" wire:model.live="selectAll" class="rounded-sm border-zinc-300 dark:border-zinc-700 text-indigo-600 focus:ring-indigo-500 dark:bg-zinc-800">
                        </th>
                        @endrole
                        <th class="py-3 px-4">Dokumen</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4">Klasifikasi</th>
                        <th class="py-3 px-4">Pengunggah</th>
                        <th class="py-3 px-4">Waktu Simpan</th>
                        <th class="py-3 px-4 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @forelse ($archives as $archive)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition">
                            @role('Pimpinan')
                            <td class="py-3 px-4">
                                <input type="checkbox" wire:model.live="selected" value="{{ $archive->id }}" class="rounded-sm border-zinc-300 dark:border-zinc-700 text-indigo-600 focus:ring-indigo-500 dark:bg-zinc-800">
                            </td>
                            @endrole
                            <td class="py-3 px-4">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $archive->title }}</div>
                                <div class="text-[11px] text-zinc-400 dark:text-zinc-500 font-mono mt-0.5">{{ $archive->document_number ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4 text-zinc-600 dark:text-zinc-300">
                                {{ $archive->category->name ?? '-' }}
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $level = $archive->classification?->level ?? 1;
                                    $badgeClass = match ($level) {
                                        4 => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border-rose-200 dark:border-rose-800',
                                        3 => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                                        2 => 'bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-400 border-sky-200 dark:border-sky-800',
                                        default => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium border {{ $badgeClass }}">
                                    {{ $archive->classification?->name ?? 'Biasa' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400">
                                {{ $archive->uploader->name ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ $archive->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="py-3 px-4 text-end">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <a 
                                        href="{{ route('archives.preview', $archive->id) }}" 
                                        title="Pratinjau Dokumen" 
                                        class="p-1.5 text-zinc-500 hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition">
                                        <flux:icon icon="eye" class="size-4" />
                                    </a>
                                    <a 
                                        href="{{ route('archives.download', $archive->id) }}" 
                                        title="Unduh File" 
                                        class="p-1.5 text-zinc-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition">
                                        <flux:icon icon="arrow-down-tray" class="size-4" />
                                    </a>
                                    <button 
                                        type="button" 
                                        wire:click="confirmDelete({{ $archive->id }})"
                                        title="Hapus Arsip" 
                                        class="p-1.5 text-zinc-500 hover:text-rose-600 dark:text-zinc-400 dark:hover:text-rose-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition">
                                        <flux:icon icon="trash" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->hasRole('Pimpinan') ? 7 : 6 }}" class="py-8 text-center text-zinc-400 dark:text-zinc-500">
                                <flux:icon icon="folder-open" class="size-8 mx-auto mb-2 opacity-50" />
                                <p>Tidak ada dokumen arsip yang ditemukan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($archives->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-850">
                {{ $archives->links() }}
            </div>
        @endif
    </div>

    {{-- Delete Reason Modal --}}
    <flux:modal name="delete-reason-modal" class="md:w-96">
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Konfirmasi Penghapusan</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Silakan masukkan alasan penghapusan arsip ini. Alasan ini akan tercatat dalam riwayat (Trash).
            </p>

            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Alasan Hapus</label>
                <textarea 
                    wire:model="deleteReason" 
                    rows="3" 
                    class="w-full text-sm rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500"
                    placeholder="Contoh: Dokumen ganda, sudah kedaluwarsa..."></textarea>
                @error('deleteReason') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <flux:button variant="subtle" wire:click="$dispatch('close-modal', 'delete-reason-modal')">Batal</flux:button>
                <flux:button variant="danger" wire:click="executeDelete" wire:loading.attr="disabled">Hapus Arsip</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
