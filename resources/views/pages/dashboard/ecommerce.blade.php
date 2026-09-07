@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Dashboard Ringkasan</h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Statistik dan tren pengelolaan arsip digital Puskesmas.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                <span class="size-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400 animate-pulse"></span>
                <span>Sistem Aktif</span>
            </span>
        </div>
    </div>

    @role('Pimpinan')
        @php
            $quotaGB = (float) env('STORAGE_QUOTA_GB', 10);
            $quotaBytes = $quotaGB * 1024 * 1024 * 1024;
            
            $activeSize = $active_size ?? 0;
            $expiredSize = $expired_size ?? 0;
            $trashSize = $trash_size ?? 0;
            $totalSize = $activeSize + $expiredSize + $trashSize;

            $activePct = $quotaBytes > 0 ? ($activeSize / $quotaBytes) * 100 : 0;
            $expiredPct = $quotaBytes > 0 ? ($expiredSize / $quotaBytes) * 100 : 0;
            $trashPct = $quotaBytes > 0 ? ($trashSize / $quotaBytes) * 100 : 0;

            $formatBytes = function($bytes) {
                if ($bytes <= 0) return '0 B';
                $k = 1024;
                $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                $i = floor(log($bytes) / log($k));
                return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
            };
        @endphp
        
        <flux:card class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-2">
                <div>
                    <flux:heading size="lg">Penggunaan Kapasitas Penyimpanan</flux:heading>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Kuota: {{ $formatBytes($quotaBytes) }}</p>
                </div>
                <div class="text-left sm:text-right">
                    <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $formatBytes($totalSize) }}</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 ml-1">terpakai</span>
                </div>
            </div>

            {{-- Progress Bar Container --}}
            <div class="w-full h-4 bg-zinc-100 dark:bg-zinc-800 rounded-full flex overflow-hidden">
                @if($activePct > 0)
                    <div class="bg-emerald-500 h-full" style="width: {{ $activePct }}%" title="Aktif: {{ $formatBytes($activeSize) }}"></div>
                @endif
                @if($expiredPct > 0)
                    <a href="{{ route('archives.index', ['filter' => 'expired']) }}" wire:navigate class="bg-amber-400 h-full hover:opacity-80 transition cursor-pointer block" style="width: {{ $expiredPct }}%" title="Kedaluwarsa: {{ $formatBytes($expiredSize) }}"></a>
                @endif
                @if($trashPct > 0)
                    <a href="{{ route('archives.trash') }}" wire:navigate class="bg-red-500 h-full hover:opacity-80 transition cursor-pointer block" style="width: {{ $trashPct }}%" title="Sampah: {{ $formatBytes($trashSize) }}"></a>
                @endif
            </div>
            
            {{-- Legends --}}
            <div class="mt-4 flex flex-wrap gap-4 text-xs">
                <div class="flex items-center gap-1.5">
                    <div class="size-3 rounded-full bg-emerald-500"></div>
                    <span class="text-zinc-600 dark:text-zinc-400">Aktif ({{ $formatBytes($activeSize) }})</span>
                </div>
                <a href="{{ route('archives.index', ['filter' => 'expired']) }}" wire:navigate class="flex items-center gap-1.5 hover:opacity-80 transition cursor-pointer">
                    <div class="size-3 rounded-full bg-amber-400"></div>
                    <span class="text-zinc-600 dark:text-zinc-400">Kedaluwarsa ({{ $formatBytes($expiredSize) }})</span>
                </a>
                <a href="{{ route('archives.trash') }}" wire:navigate class="flex items-center gap-1.5 hover:opacity-80 transition cursor-pointer">
                    <div class="size-3 rounded-full bg-red-500"></div>
                    <span class="text-zinc-600 dark:text-zinc-400">Sampah ({{ $formatBytes($trashSize) }})</span>
                </a>
            </div>
        </flux:card>
    @endrole

    @php
        $monthlySeries = $monthlyStats['series'] ?? array_fill(0, 12, 0);
        $monthlyLabels = $monthlyStats['labels'] ?? ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $maxMonthly = max(max($monthlySeries), 1);
        $totalThisYear = array_sum($monthlySeries);

        $classLabels = $classificationStats['labels'] ?? [];
        $classSeries = $classificationStats['series'] ?? [];
        $classTotal = array_sum($classSeries);
        $classMap = [];
        foreach ($classLabels as $idx => $label) {
            $count = $classSeries[$idx] ?? 0;
            $classMap[$label] = [
                'total' => $count,
                'pct' => $classTotal > 0 ? ($count / $classTotal) * 100 : 0,
            ];
        }

        $aiAvgConfidence = $aiStats['average_confidence'] ?? 0;
        $aiLowCount = $aiStats['low_confidence_count'] ?? 0;
        $aiProcessedCount = $aiStats['total_ai_processed'] ?? 0;
    @endphp

    {{-- Top Metrics Grid: 3 Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Card 1: Total Arsip --}}
        <flux:card class="flex flex-col justify-between p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Arsip Tersimpan</span>
                <div class="size-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <flux:icon icon="folder" class="size-4" />
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">{{ $totalArchives ?? 0 }}</span>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">dokumen</span>
            </div>
            <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-[11px] text-zinc-500">
                <span>Sinkronisasi</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-medium">Real-time</span>
            </div>
        </flux:card>

        {{-- Card 2: AI Triage & Akurasi Model --}}
        <flux:card class="flex flex-col justify-between p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Skor Keyakinan AI</span>
                <div class="size-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <flux:icon icon="sparkles" class="size-4" />
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">{{ $aiAvgConfidence }}%</span>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">rata-rata akurasi</span>
            </div>
            <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-[11px]">
                <span class="text-zinc-500">Total Diproses</span>
                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $aiProcessedCount }} arsip</span>
            </div>
        </flux:card>

        {{-- Card 3: Antrean Verifikasi AI (Actionable Triage) --}}
        <flux:card class="flex flex-col justify-between p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Antrean Verifikasi AI</span>
                <div class="size-8 rounded-lg {{ $aiLowCount > 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400' : 'bg-zinc-50 dark:bg-zinc-800 text-zinc-500' }} flex items-center justify-center">
                    <flux:icon icon="exclamation-circle" class="size-4" />
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-bold {{ $aiLowCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-900 dark:text-white' }} tracking-tight">{{ $aiLowCount }}</span>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">arsip (&lt; 70%)</span>
            </div>
            <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-[11px]">
                @if($aiLowCount > 0)
                    <a href="{{ route('archives.index', ['filter' => 'low_confidence']) }}" wire:navigate class="inline-flex items-center gap-1 font-medium text-amber-600 dark:text-amber-400 hover:underline">
                        <span>Reviu sekarang</span>
                        <flux:icon icon="arrow-right" class="size-3" />
                    </a>
                @else
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">Semua tervalidasi baik</span>
                @endif
            </div>
        </flux:card>
    </div>

    {{-- Middle Section: Monthly Trends & Top Categories --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Pure CSS Monthly Trend Chart (2 Cols) --}}
        <flux:card class="lg:col-span-2 p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Tren Unggahan Dokumen ({{ date('Y') }})</h3>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Distribusi penambahan dokumen baru setiap bulan</p>
                </div>
                <span class="text-xs font-medium px-2 py-1 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 rounded-lg">
                    Total: {{ $totalThisYear }} dokumen
                </span>
            </div>

            {{-- Pure CSS Flex Bar Chart --}}
            <div class="flex items-end justify-between h-40 gap-1.5 sm:gap-3 pt-6 pb-2 px-1 border-b border-zinc-100 dark:border-zinc-800">
                @foreach($monthlySeries as $idx => $val)
                    @php
                        $barPct = $val > 0 ? max(8, round(($val / $maxMonthly) * 100)) : 4;
                        $label = $monthlyLabels[$idx] ?? '';
                    @endphp
                    <div class="group relative flex flex-col items-center flex-1 h-full justify-end">
                        {{-- Hover Tooltip --}}
                        <div class="absolute -top-7 opacity-0 group-hover:opacity-100 transition-opacity bg-zinc-900 text-white text-[10px] font-medium py-0.5 px-1.5 rounded shadow-sm pointer-events-none whitespace-nowrap z-20">
                            {{ $val }} dokumen
                        </div>
                        
                        {{-- Bar --}}
                        <div class="w-full max-w-[28px] {{ $val > 0 ? 'bg-emerald-500 hover:bg-emerald-600 dark:bg-emerald-600 dark:hover:bg-emerald-500' : 'bg-zinc-100 dark:bg-zinc-800' }} rounded-t transition-all duration-200 cursor-pointer" style="height: {{ $barPct }}%"></div>
                        
                        {{-- Month Label --}}
                        <span class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-2 font-medium">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
            
            <div class="mt-3 flex items-center justify-between text-[11px] text-zinc-400">
                <span>Rata-rata: {{ round($totalThisYear / 12, 1) }} dok/bln</span>
                <span class="flex items-center gap-1.5">
                    <span class="size-2 rounded-full bg-emerald-500"></span> Dokumen terunggah
                </span>
            </div>
        </flux:card>

        {{-- Top 5 Categories (1 Col) --}}
        <flux:card class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Komposisi Kategori Arsip</h3>
                    <flux:icon icon="tag" class="size-4 text-zinc-400" />
                </div>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mb-4">Top 5 bidang dokumen terbanyak</p>

                <div class="space-y-3.5">
                    @forelse($categoryStats['items'] ?? [] as $cat)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="font-medium text-zinc-700 dark:text-zinc-300 truncate max-w-[160px]">{{ $cat['name'] }}</span>
                                <span class="text-zinc-500 font-mono text-[11px]">{{ $cat['total'] }} ({{ $cat['percentage'] }}%)</span>
                            </div>
                            <div class="w-full h-2 bg-zinc-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $cat['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-xs text-zinc-400">
                            Belum ada arsip terdata
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex justify-between text-[11px] text-zinc-400">
                <span>Total Kategori</span>
                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ count($categoryStats['labels'] ?? []) }} aktif</span>
            </div>
        </flux:card>
    </div>

    {{-- Security Level Breakdown Card --}}
    <flux:card class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 gap-1">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Tingkat Kerahasiaan Dokumen</h3>
                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Distribusi level keamanan dan hak akses arsip</p>
            </div>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">Total: {{ $classTotal }} dokumen</span>
        </div>

        {{-- Multi-segment Bar --}}
        <div class="w-full h-3.5 bg-zinc-100 dark:bg-zinc-800 rounded-full flex overflow-hidden">
            @if(($classMap['Biasa']['pct'] ?? 0) > 0)
                <div class="bg-emerald-500 h-full" style="width: {{ $classMap['Biasa']['pct'] }}%" title="Biasa: {{ $classMap['Biasa']['total'] ?? 0 }}"></div>
            @endif
            @if(($classMap['Terbatas']['pct'] ?? 0) > 0)
                <div class="bg-blue-500 h-full" style="width: {{ $classMap['Terbatas']['pct'] }}%" title="Terbatas: {{ $classMap['Terbatas']['total'] ?? 0 }}"></div>
            @endif
            @if(($classMap['Rahasia']['pct'] ?? 0) > 0)
                <div class="bg-amber-500 h-full" style="width: {{ $classMap['Rahasia']['pct'] }}%" title="Rahasia: {{ $classMap['Rahasia']['total'] ?? 0 }}"></div>
            @endif
            @if(($classMap['Sangat Rahasia']['pct'] ?? 0) > 0)
                <div class="bg-rose-500 h-full" style="width: {{ $classMap['Sangat Rahasia']['pct'] }}%" title="Sangat Rahasia: {{ $classMap['Sangat Rahasia']['total'] ?? 0 }}"></div>
            @endif
        </div>

        {{-- Level Badges Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
            {{-- Level 1: Biasa --}}
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-lg border border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2 mb-1">
                    <div class="size-2 rounded-full bg-emerald-500"></div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Biasa</span>
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $classMap['Biasa']['total'] ?? 0 }}</span>
                    <span class="text-[11px] text-zinc-500">{{ round($classMap['Biasa']['pct'] ?? 0, 1) }}%</span>
                </div>
            </div>

            {{-- Level 2: Terbatas --}}
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-lg border border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2 mb-1">
                    <div class="size-2 rounded-full bg-blue-500"></div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Terbatas</span>
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $classMap['Terbatas']['total'] ?? 0 }}</span>
                    <span class="text-[11px] text-zinc-500">{{ round($classMap['Terbatas']['pct'] ?? 0, 1) }}%</span>
                </div>
            </div>

            {{-- Level 3: Rahasia --}}
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-lg border border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2 mb-1">
                    <div class="size-2 rounded-full bg-amber-500"></div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Rahasia</span>
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $classMap['Rahasia']['total'] ?? 0 }}</span>
                    <span class="text-[11px] text-zinc-500">{{ round($classMap['Rahasia']['pct'] ?? 0, 1) }}%</span>
                </div>
            </div>

            {{-- Level 4: Sangat Rahasia --}}
            <div class="p-3 bg-zinc-50 dark:bg-zinc-800/60 rounded-lg border border-zinc-100 dark:border-zinc-800">
                <div class="flex items-center gap-2 mb-1">
                    <div class="size-2 rounded-full bg-rose-500"></div>
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Sangat Rahasia</span>
                </div>
                <div class="flex items-baseline justify-between">
                    <span class="text-lg font-bold text-zinc-900 dark:text-white">{{ $classMap['Sangat Rahasia']['total'] ?? 0 }}</span>
                    <span class="text-[11px] text-zinc-500">{{ round($classMap['Sangat Rahasia']['pct'] ?? 0, 1) }}%</span>
                </div>
            </div>
        </div>
    </flux:card>
  </div>
@endsection
