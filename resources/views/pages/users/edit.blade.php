@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Edit Data Pengguna</h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Perbarui profil pengguna dan hak akses peran.</p>
        </div>
        <a href="{{ route('users.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
            <flux:icon icon="arrow-left" class="size-3.5" />
            <span>Kembali</span>
        </a>
    </div>

    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle">
            <p class="font-semibold text-xs mb-1">Terjadi kesalahan pengisian:</p>
            <ul class="list-disc pl-4 space-y-0.5 text-xs">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </flux:callout>
    @endif

    <form action="{{ route('users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Panel Kiri: Informasi Profil (7 Kolom) --}}
            <div class="lg:col-span-7 space-y-6">
                <flux:card class="space-y-5">
                    <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">1. Informasi Profil Pegawai</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Identitas dasar pengguna untuk keperluan login dan audit trail.</p>
                    </div>

                    <div>
                        <flux:field>
                            <flux:label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Nama Lengkap <span class="text-rose-500">*</span></flux:label>
                            <flux:input type="text" name="name" value="{{ old('name', $user->name) }}" required />
                            <flux:error name="name" />
                        </flux:field>
                    </div>

                    <div>
                        <flux:field>
                            <flux:label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Alamat Email <span class="text-rose-500">*</span></flux:label>
                            <flux:input type="email" name="email" value="{{ old('email', $user->email) }}" required />
                            <flux:error name="email" />
                        </flux:field>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                        <div>
                            <flux:field>
                                <flux:label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Password Baru <span class="text-zinc-400 font-normal">(opsional)</span></flux:label>
                                <flux:input type="password" name="password" placeholder="Biarkan kosong jika tetap" viewable />
                                <flux:error name="password" />
                            </flux:field>
                        </div>
                        <div>
                            <flux:field>
                                <flux:label class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Konfirmasi Password Baru</flux:label>
                                <flux:input type="password" name="password_confirmation" placeholder="Ulangi password baru" viewable />
                                <flux:error name="password_confirmation" />
                            </flux:field>
                        </div>
                    </div>
                </flux:card>
            </div>

            {{-- Panel Kanan: Hak Akses & Peran (5 Kolom) --}}
            <div class="lg:col-span-5 space-y-6">
                <flux:card class="space-y-5">
                    <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">2. Hak Akses & Peran</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Perbarui tingkat otorisasi sistem untuk akun ini.</p>
                    </div>

                    <div class="space-y-3">
                        <flux:label class="text-xs font-medium text-zinc-700 dark:text-zinc-300 block">Pilih Peran Akun <span class="text-rose-500">*</span></flux:label>
                        <div class="space-y-2.5">
                            @foreach($roles as $role)
                                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-zinc-200 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer transition">
                                    <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="mt-0.5 size-4 rounded text-emerald-600 focus:ring-emerald-500 border-zinc-300 dark:border-zinc-700" @if($user->hasRole($role->name)) checked @endif>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold text-zinc-900 dark:text-white">{{ $role->name }}</span>
                                            <flux:badge size="sm" :color="$role->name === 'Pimpinan' ? 'purple' : 'emerald'">
                                                {{ $role->name === 'Pimpinan' ? 'Level 1-4' : 'Level 1-3' }}
                                            </flux:badge>
                                        </div>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                            {{ $role->name === 'Pimpinan' ? 'Hak akses penuh melihat seluruh level dokumen (1-4) dan konfigurasi AI.' : 'Operasional harian dan pengelolaan dokumen standar (Level 1-3).' }}
                                        </p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                        @error('roles')
                            <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-end gap-2">
                        <a href="{{ route('users.index') }}" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition">Batal</a>
                        <flux:button type="submit" variant="primary" icon="check" size="sm">
                            Simpan Perubahan
                        </flux:button>
                    </div>
                </flux:card>
            </div>
        </div>
    </form>
</div>
@endsection
