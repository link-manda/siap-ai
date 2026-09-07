<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-zinc-50 dark:bg-zinc-950">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'SIAP-AI' }} | Sistem Informasi Arsip Puskesmas</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>

<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 font-sans text-zinc-800 dark:text-zinc-200 antialiased">
    {{-- Flux Collapsible Sidebar for Desktop & Mobile --}}
    <flux:sidebar sticky collapsible class="bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800">
        <flux:sidebar.header>
            <flux:sidebar.brand 
                name="SIAP-AI" 
                href="{{ route('dashboard') }}"
            >
                <div class="flex size-6 items-center justify-center rounded bg-emerald-600 text-white font-bold text-xs shadow-sm">
                    SA
                </div>
            </flux:sidebar.brand>
            <flux:sidebar.collapse />
        </flux:sidebar.header>

        <flux:separator class="my-2" />

        <flux:sidebar.nav>
            @foreach (\App\Helpers\MenuHelper::getMainNavItems() as $item)
                <flux:sidebar.item 
                    :icon="$item['icon']" 
                    :href="$item['path']" 
                    :current="\App\Helpers\MenuHelper::isActive($item['path'])">
                    {{ $item['name'] }}
                </flux:sidebar.item>
            @endforeach
        </flux:sidebar.nav>

        <flux:spacer />
    </flux:sidebar>

    {{-- Persistent Top Header (Desktop & Mobile) --}}
    <flux:header class="bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 px-4">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        
        <div class="flex items-center gap-2 max-lg:ms-2">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 hidden sm:inline">Sistem Informasi Arsip Puskesmas</span>
        </div>

        <flux:spacer />

        <div class="flex items-center gap-3">
            {{-- Theme Mode Toggle Button --}}
            <flux:button 
                x-data 
                x-on:click="$flux.appearance = $flux.appearance === 'dark' ? 'light' : 'dark'" 
                variant="subtle" 
                size="sm"
                square 
                aria-label="Toggle dark mode">
                <flux:icon icon="sun" class="size-4 hidden dark:block text-zinc-400 hover:text-zinc-200" />
                <flux:icon icon="moon" class="size-4 block dark:hidden text-zinc-500 hover:text-zinc-800" />
            </flux:button>

            {{-- User Profile Dropdown --}}
            <flux:dropdown position="bottom" align="end">
                <flux:profile 
                    avatar="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=059669&color=fff" 
                    name="{{ auth()->user()->name ?? 'User' }}" />

                <flux:menu>
                    <div class="px-3 py-2 border-b border-zinc-100 dark:border-zinc-800">
                        <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ auth()->user()->name ?? 'User' }}</p>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                    <flux:menu.item icon="arrow-right-start-on-rectangle" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        Keluar
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:header>

    {{-- Main Content Area --}}
    <flux:main>
        <div class="mx-auto max-w-7xl">
            @yield('content')
        </div>
    </flux:main>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
        @csrf
    </form>

    <flux:toast position="top right" />
    @include('partials.flash-toasts')

    @fluxScripts
    @stack('scripts')
</body>

</html>
