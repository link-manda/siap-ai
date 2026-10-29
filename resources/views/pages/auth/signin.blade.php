@extends('layouts.fullscreen-layout')

@section('content')
    <div class="relative flex min-h-screen w-full flex-col lg:flex-row bg-white dark:bg-zinc-950">

        <!-- Left Side: Hero Image (Hidden on mobile, 50% on large screens) -->
        <div class="hidden lg:block lg:w-1/2 relative bg-zinc-100 dark:bg-zinc-900">
            <!-- Hero Image filling the container -->
            <img
                src="{{ asset('images/hero.jpg') }}"
                alt="Fasilitas Kesehatan SIAP-AI"
                class="absolute inset-0 w-full h-full object-cover"
            />
            <!-- Subtle Overlay -->
            <div class="absolute inset-0 bg-zinc-900/5 mix-blend-multiply dark:bg-zinc-950/20"></div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="flex w-full lg:w-1/2 flex-col justify-center px-6 py-12 sm:px-12 lg:px-24">
            <div class="mx-auto w-full max-w-sm">
                <!-- Branding Header -->
                <div class="mb-10 text-center lg:text-left">
                    <div class="flex items-center justify-center lg:justify-start mb-6">
                        <img
                            src="{{ asset('images/logo-puskesmas.png') }}"
                            alt="Logo Puskesmas"
                            class="h-14 w-auto object-contain"
                        />
                    </div>

                    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                        Masuk ke SIAP-AI
                    </h1>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                        Silakan masukkan kredensial Anda untuk mengakses sistem.
                    </p>
                </div>

                <!-- Form -->
                <form action="{{ route('login.post') }}" method="POST" class="space-y-6">
                    @csrf

                    <!-- Email -->
                    <flux:field>
                        <flux:label>Email Pengguna</flux:label>
                        <flux:input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="nama@puskesmas.go.id"
                            required
                            autofocus
                        />
                        <flux:error name="email" />
                    </flux:field>

                    <!-- Password -->
                    <flux:field>
                        <div class="flex items-center justify-between mb-2">
                            <flux:label>Kata Sandi</flux:label>
                            <a href="#" class="text-sm font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-500 dark:hover:text-emerald-400 transition-colors">
                                Lupa kata sandi?
                            </a>
                        </div>
                        <flux:input
                            type="password"
                            name="password"
                            placeholder="••••••••"
                            required
                            viewable
                        />
                        <flux:error name="password" />
                    </flux:field>

                    <!-- Remember Me -->
                    <flux:checkbox
                        name="remember"
                        label="Ingat saya di perangkat ini"
                    />

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <flux:button type="submit" variant="primary" class="w-full">
                            Masuk ke Sistem
                        </flux:button>
                    </div>
                </form>

                <!-- Footer -->
                <div class="mt-10 text-center lg:text-left">
                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">
                        Sistem Informasi Kearsipan Digital & Manajemen Rekam Medis Terintegrasi AI
                    </p>
                </div>
            </div>
        </div>

    </div>
@endsection
