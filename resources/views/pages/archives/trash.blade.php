@extends('layouts.app')

@section('content')
<div class="space-y-6">
    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Tong Sampah (Trash)</h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Riwayat dokumen arsip yang telah dihapus beserta alasannya.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('archives.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                <flux:icon icon="arrow-left" class="size-3.5" />
                <span>Kembali ke Arsip</span>
            </a>
        </div>
    </div>

    {{-- DataTable Area --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/75 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">Dokumen</th>
                        <th class="py-3 px-4">Kategori / Klasifikasi</th>
                        <th class="py-3 px-4">Alasan Dihapus</th>
                        <th class="py-3 px-4">Tanggal Dihapus</th>
                        <th class="py-3 px-4 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @forelse ($archives as $archive)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition">
                            <td class="py-3 px-4">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $archive->title }}</div>
                                <div class="text-[11px] text-zinc-400 dark:text-zinc-500 font-mono mt-0.5">{{ $archive->document_number ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4 text-zinc-600 dark:text-zinc-300">
                                <div>{{ $archive->category->name ?? '-' }}</div>
                                <div class="text-[11px] text-zinc-400 mt-0.5">{{ $archive->classification?->name ?? 'Biasa' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-rose-600 dark:text-rose-400 italic">"{{ $archive->delete_reason ?? 'Tidak ada alasan' }}"</span>
                            </td>
                            <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ $archive->deleted_at->format('d M Y, H:i') }}
                            </td>
                            <td class="py-3 px-4 text-end">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <button 
                                        type="button" 
                                        x-data 
                                        x-on:click="$dispatch('modal-show', { name: 'restore-archive-{{ $archive->id }}' })"
                                        title="Restore" 
                                        class="p-1.5 text-zinc-500 hover:text-emerald-600 dark:text-zinc-400 dark:hover:text-emerald-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition">
                                        <flux:icon icon="arrow-uturn-left" class="size-4" />
                                    </button>

                                    <button 
                                        type="button" 
                                        x-data 
                                        x-on:click="$dispatch('modal-show', { name: 'force-delete-archive-{{ $archive->id }}' })"
                                        title="Hapus Permanen" 
                                        class="p-1.5 text-zinc-500 hover:text-rose-600 dark:text-zinc-400 dark:hover:text-rose-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition">
                                        <flux:icon icon="fire" class="size-4" />
                                    </button>
                                </div>

                                {{-- Modal Restore --}}
                                <flux:modal name="restore-archive-{{ $archive->id }}" class="md:w-96 text-left">
                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <flux:heading size="lg" class="text-zinc-900 dark:text-white font-bold">Pulihkan Arsip</flux:heading>
                                            <flux:subheading class="text-xs text-zinc-600 dark:text-zinc-400 whitespace-normal">
                                                Kembalikan dokumen <strong class="text-zinc-900 dark:text-white">{{ $archive->title }}</strong> ke daftar arsip aktif?
                                            </flux:subheading>
                                        </div>
                                        <div class="flex justify-end gap-2 mt-4 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                            <flux:modal.close>
                                                <flux:button variant="subtle">Batal</flux:button>
                                            </flux:modal.close>
                                            <form action="{{ route('archives.restore', $archive->id) }}" method="POST">
                                                @csrf
                                                <flux:button type="submit" variant="primary" class="bg-emerald-600 hover:bg-emerald-500 text-white">Pulihkan</flux:button>
                                            </form>
                                        </div>
                                    </div>
                                </flux:modal>

                                {{-- Modal Force Delete --}}
                                <flux:modal name="force-delete-archive-{{ $archive->id }}" class="md:w-96 text-left">
                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <flux:heading size="lg" class="text-zinc-900 dark:text-white font-bold">Hapus Arsip Permanen</flux:heading>
                                            <flux:subheading class="text-xs text-zinc-600 dark:text-zinc-400 whitespace-normal">
                                                @if($archive->attachments_count > 0)
                                                    <span class="block mb-2 text-rose-600 dark:text-rose-400 font-medium">Arsip ini memiliki {{ $archive->attachments_count }} lampiran pendukung di tempat sampah. Menghapus permanen arsip ini akan melenyapkan seluruh file lampiran secara fisik.</span>
                                                @endif
                                                PERINGATAN: Tindakan ini akan menghapus arsip <strong class="text-zinc-900 dark:text-white">{{ $archive->title }}</strong> secara permanen dari sistem dan tidak dapat dibatalkan. Lanjutkan?
                                            </flux:subheading>
                                        </div>
                                        <div class="flex justify-end gap-2 mt-4 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                                            <flux:modal.close>
                                                <flux:button variant="subtle">Batal</flux:button>
                                            </flux:modal.close>
                                            <form action="{{ route('archives.force-destroy', $archive->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <flux:button type="submit" variant="danger">Hapus Permanen</flux:button>
                                            </form>
                                        </div>
                                    </div>
                                </flux:modal>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-zinc-400 dark:text-zinc-500">
                                <flux:icon icon="trash" class="size-8 mx-auto mb-2 opacity-50" />
                                <p>Tidak ada dokumen arsip di tong sampah.</p>
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
</div>
@endsection
