@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative z-1 bg-white p-6 sm:p-0 dark:bg-zinc-950">
        <div class="relative flex min-h-screen w-full flex-col justify-center sm:p-0 lg:flex-row dark:bg-zinc-950">
            <!-- Form Sisi Kiri -->
            <div class="flex w-full flex-1 flex-col justify-center px-4 sm:px-6 lg:w-1/2 lg:px-12 py-12">
                <div class="mx-auto flex w-full max-w-md flex-col justify-center">
                    <div class="mb-8">
                        <div class="flex items-center gap-3 mb-4 lg:hidden">
                            <div class="size-10 rounded-xl bg-indigo-600 text-white font-bold text-lg flex items-center justify-center shadow-md shadow-indigo-500/20">
                                SA
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-zinc-900 dark:text-white leading-none">SIAP-AI</h2>
                                <p class="text-[11px] text-zinc-500">Puskesmas Digital</p>
                            </div>
                        </div>

                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white tracking-tight">
                            Masuk ke Sistem
                        </h1>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            Masukkan email dan kata sandi Anda untuk mengakses arsip.
                        </p>
                    </div>

                    <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                        @csrf

                        <!-- Email -->
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                Email Pengguna
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                placeholder="nama@puskesmas.go.id" 
                                required 
                                autofocus
                                class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 px-3.5 py-2.5 text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500 transition shadow-xs"
                            />
                            @error('email')
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    Kata Sandi
                                </label>
                            </div>
                            <div x-data="{ showPassword: false }" class="relative">
                                <input 
                                    :type="showPassword ? 'text' : 'password'" 
                                    id="password" 
                                    name="password" 
                                    required 
                                    placeholder="••••••••" 
                                    class="w-full text-xs rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 px-3.5 py-2.5 pr-10 text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 focus:border-indigo-500 focus:outline-hidden focus:ring-1 focus:ring-indigo-500 transition shadow-xs"
                                />
                                <button 
                                    type="button" 
                                    @click="showPassword = !showPassword" 
                                    class="absolute top-1/2 right-3 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                                >
                                    <svg x-show="!showPassword" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none">
                                <input 
                                    type="checkbox" 
                                    name="remember" 
                                    class="size-4 rounded border-zinc-300 dark:border-zinc-700 text-indigo-600 focus:ring-indigo-500 dark:bg-zinc-900"
                                />
                                <span class="text-xs text-zinc-600 dark:text-zinc-400">Ingat saya di perangkat ini</span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button 
                                type="submit" 
                                class="w-full flex justify-center items-center py-2.5 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs shadow-sm hover:shadow transition focus:outline-hidden focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
                            >
                                Masuk ke Sistem
                            </button>
                        </div>
                    </form>

                    <div class="mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800 text-center">
                        <p class="text-[11px] text-zinc-400 dark:text-zinc-500">
                            Sistem Informasi Kearsipan Digital & Rekam Medis Terintegrasi AI
                        </p>
                    </div>
                </div>
            </div>

            <!-- Banner Sisi Kanan (Branding SIAP-AI) -->
            <div class="relative hidden h-full w-full items-center justify-center lg:flex lg:w-1/2 bg-zinc-900 dark:bg-zinc-900/60 overflow-hidden">
                <!-- Background Grid Shape -->
                <x-common.common-grid-shape />

                <div class="relative z-10 flex flex-col items-center text-center p-8 max-w-sm">
                    <div class="size-20 rounded-2xl bg-indigo-600 text-white font-bold text-3xl flex items-center justify-center shadow-xl shadow-indigo-500/25 mb-6">
                        SA
                    </div>
                    <h2 class="text-2xl font-bold text-white tracking-tight">SIAP-AI</h2>
                    <p class="text-xs text-zinc-400 mt-2 leading-relaxed">
                        Sistem Informasi Kearsipan & Manajemen Rekam Medis Cerdas Terintegrasi AI untuk efisiensi dan tata kelola arsip digital Puskesmas.
                    </p>
                    <div class="mt-6 inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-zinc-800/80 border border-zinc-700/50 text-[11px] text-zinc-300">
                        <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span>Level Keamanan Berlapis (RBAC Level 1-4)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
