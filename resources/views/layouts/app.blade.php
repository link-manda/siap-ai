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

<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 text-zinc-800 dark:text-zinc-200 antialiased">
    {{-- Flux Sidebar for Desktop & Mobile --}}
    <flux:sidebar sticky stashable class="bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <div class="flex items-center gap-2.5 px-2 py-1">
            <div class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-sm shadow-sm">
                SA
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-semibold text-zinc-900 dark:text-white leading-tight">SIAP-AI</span>
                <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Puskesmas Digital</span>
            </div>
        </div>

        <flux:separator class="my-2" />

        <flux:navlist variant="outline">
            @foreach (\App\Helpers\MenuHelper::getMainNavItems() as $item)
                <flux:navlist.item 
                    :icon="$item['icon']" 
                    :href="$item['path']" 
                    :current="\App\Helpers\MenuHelper::isActive($item['path'])">
                    {{ $item['name'] }}
                </flux:navlist.item>
            @endforeach
        </flux:navlist>

        <flux:spacer />

        {{-- Theme & Profile Area --}}
        <div class="flex flex-col gap-2 pt-4 border-t border-zinc-200 dark:border-zinc-800">
            <div class="px-2 flex items-center justify-between">
                <span class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Mode Tampilan</span>
                <flux:button 
                    x-data 
                    x-on:click="$flux.appearance = $flux.appearance === 'dark' ? 'light' : 'dark'" 
                    variant="subtle" 
                    size="sm"
                    square 
                    aria-label="Toggle dark mode">
                    <flux:icon icon="sun" class="size-4 hidden dark:block" />
                    <flux:icon icon="moon" class="size-4 block dark:hidden" />
                </flux:button>
            </div>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:profile 
                    avatar="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=4f46e5&color=fff" 
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
    </flux:sidebar>

    {{-- Mobile Header --}}
    <flux:header class="lg:hidden bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        <flux:spacer />
        <flux:dropdown position="bottom" align="end">
            <flux:profile avatar="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=4f46e5&color=fff" />
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

    @fluxScripts
    @stack('scripts')
</body>

</html>
