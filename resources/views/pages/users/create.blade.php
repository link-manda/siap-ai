@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Tambah Pengguna Baru</h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Daftarkan akun pegawai baru dan tentukan hak akses peran.</p>
        </div>
        <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-zinc-800 rounded-lg transition">
            <flux:icon icon="arrow-left" class="size-3.5" />
            <span>Kembali</span>
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-lg bg-rose-50 dark:bg-rose-950/40 p-4 border border-rose-200 dark:border-rose-800 text-xs text-rose-700 dark:text-rose-400">
            <p class="font-semibold mb-1">Terjadi kesalahan pengisian:</p>
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs p-6">
        <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500" required>
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Alamat Email <span class="text-rose-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Password <span class="text-rose-500">*</span></label>
                    <input type="password" name="password" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500" required>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1">Konfirmasi Password <span class="text-rose-500">*</span></label>
                    <input type="password" name="password_confirmation" class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2 text-zinc-900 dark:text-white focus:outline-hidden focus:ring-1 focus:ring-indigo-500" required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-2">Peran Akses (Role) <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($roles as $role)
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer transition">
                            <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="mt-0.5 size-4 rounded text-indigo-600 focus:ring-indigo-500 border-zinc-300 dark:border-zinc-700">
                            <div>
                                <span class="block text-xs font-semibold text-zinc-900 dark:text-white">{{ $role->name }}</span>
                                <span class="block text-[11px] text-zinc-500 dark:text-zinc-400">
                                    {{ $role->name === 'Pimpinan' ? 'Hak akses penuh melihat seluruh level dokumen (1-4).' : 'Operasional harian dan dokumen standar (Level 1-3).' }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800 flex justify-end gap-2">
                <a href="{{ route('users.index') }}" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition">Batal</a>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-sm transition">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
