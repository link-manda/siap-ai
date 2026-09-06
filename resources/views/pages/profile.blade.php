@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-4xl">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">Profil Pengguna</h1>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Informasi akun dan hak akses pengguna di SIAP-AI Puskesmas.</p>
    </div>

    <flux:card class="p-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 pb-6 border-b border-zinc-200 dark:border-zinc-800">
            <flux:avatar 
                size="xl" 
                src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=4f46e5&color=fff&size=128" 
            />
            <div class="space-y-1">
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">{{ auth()->user()->name }}</h2>
                    @foreach(auth()->user()->roles as $role)
                        <flux:badge size="sm" :color="$role->name === 'Pimpinan' ? 'amber' : 'blue'">
                            {{ $role->name }}
                        </flux:badge>
                    @endforeach
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 font-mono">{{ auth()->user()->email }}</p>
                <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Terdaftar sejak: {{ auth()->user()->created_at->format('d M Y H:i') }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-6">
            <div>
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 block mb-1">Nama Lengkap</span>
                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()->name }}</p>
            </div>

            <div>
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 block mb-1">Alamat Email</span>
                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()->email }}</p>
            </div>

            <div>
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 block mb-1">Peran Akses (RBAC)</span>
                <div class="flex items-center gap-1.5 flex-wrap">
                    @forelse(auth()->user()->roles as $role)
                        <flux:badge size="sm" :color="$role->name === 'Pimpinan' ? 'amber' : 'blue'">
                            {{ $role->name }}
                        </flux:badge>
                    @empty
                        <span class="text-xs text-zinc-400">Tidak ada peran</span>
                    @endforelse
                </div>
            </div>

            <div>
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 block mb-1">Status Keamanan Akun</span>
                <div class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 text-xs font-medium">
                    <flux:icon icon="shield-check" class="size-4" />
                    <span>Terotentikasi & Aktif</span>
                </div>
            </div>
        </div>
    </flux:card>
</div>
@endsection
