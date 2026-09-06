<div class="space-y-6">
    {{-- Top Action Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Manajemen Pengguna & Role</h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Kelola akun pegawai Puskesmas, peran (Pimpinan/Admin), dan hak akses sistem.</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="w-full sm:w-64">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari nama atau email..." 
                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white placeholder-zinc-400 focus:outline-hidden focus:ring-1 focus:ring-indigo-500" />
            </div>

            <a href="{{ route('users.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-sm transition shrink-0">
                <flux:icon icon="plus" class="size-3.5" />
                <span>Tambah Pengguna</span>
            </a>
        </div>
    </div>

    {{-- Table Area --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 overflow-hidden shadow-xs">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50/75 dark:bg-zinc-800/50 text-zinc-500 dark:text-zinc-400 font-semibold uppercase tracking-wider">
                        <th class="py-3 px-4">Pengguna</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4">Role Akses</th>
                        <th class="py-3 px-4">Terdaftar</th>
                        <th class="py-3 px-4 text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @forelse ($users as $user)
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="size-7 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 flex items-center justify-center font-bold text-xs uppercase">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <span class="font-medium text-zinc-900 dark:text-white">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-zinc-600 dark:text-zinc-300">
                                {{ $user->email }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($user->roles as $role)
                                        @php
                                            $roleColor = $role->name === 'Pimpinan' 
                                                ? 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400 border-purple-200 dark:border-purple-800'
                                                : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800';
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium border {{ $roleColor }}">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="text-zinc-400 text-xs italic">Tanpa Role</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3 px-4 text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                {{ $user->created_at->format('d M Y') }}
                            </td>
                            <td class="py-3 px-4 text-end">
                                <div class="inline-flex items-center gap-2 justify-end">
                                    <a 
                                        href="{{ route('users.edit', $user->id) }}" 
                                        title="Edit Pengguna" 
                                        class="p-1.5 text-zinc-500 hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition">
                                        <flux:icon icon="pencil-square" class="size-4" />
                                    </a>
                                    @if (auth()->id() !== $user->id)
                                        <button 
                                            type="button" 
                                            wire:click="deleteUser({{ $user->id }})" 
                                            wire:confirm="Yakin ingin menghapus akun pengguna '{{ $user->name }}'?" 
                                            title="Hapus Pengguna" 
                                            class="p-1.5 text-zinc-500 hover:text-rose-600 dark:text-zinc-400 dark:hover:text-rose-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-md transition">
                                            <flux:icon icon="trash" class="size-4" />
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-zinc-400 dark:text-zinc-500">
                                Tidak ada akun pengguna yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-850">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
