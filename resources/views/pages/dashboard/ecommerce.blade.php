@extends('layouts.app')

@section('content')
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-white tracking-tight">Dashboard Ringkasan</h1>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Statistik dan tren pengelolaan arsip digital Puskesmas.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800 rounded-lg">
                <span class="size-1.5 rounded-full bg-indigo-600 dark:bg-indigo-400 animate-pulse"></span>
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
                    <div class="bg-indigo-500 h-full" style="width: {{ $activePct }}%" title="Aktif: {{ $formatBytes($activeSize) }}"></div>
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
                    <div class="size-3 rounded-full bg-indigo-500"></div>
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

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Total Arsip Card --}}
        <flux:card class="flex flex-col justify-between p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Arsip Tersimpan</span>
                <div class="size-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <flux:icon icon="folder" class="size-4" />
                </div>
            </div>
            <div class="mt-4 flex items-baseline gap-2">
                <span class="text-3xl font-bold text-zinc-900 dark:text-white tracking-tight">{{ $totalArchives ?? 0 }}</span>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">dokumen</span>
            </div>
            <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex items-center justify-between text-[11px] text-zinc-500">
                <span>Status Sinkronisasi</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-medium">Real-time</span>
            </div>
        </flux:card>

        {{-- Monthly Trend Chart Card --}}
        <flux:card class="md:col-span-2 p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Tren Unggahan Dokumen ({{ date('Y') }})</h3>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Distribusi dokumen baru setiap bulan</p>
                </div>
            </div>
            <div id="monthlyChart" class="-ml-2 w-full h-32"></div>
        </flux:card>
    </div>

    {{-- Category & Classification Charts --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <flux:card class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-1">Komposisi Kategori Arsip</h3>
            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mb-4">Proporsi dokumen berdasarkan kelompok bidang</p>
            <div id="categoryChart" class="flex justify-center min-h-[260px]"></div>
        </flux:card>

        <flux:card class="p-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xs">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-1">Tingkat Kerahasiaan Dokumen</h3>
            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mb-4">Distribusi level keamanan & akses arsip</p>
            <div id="classificationChart" class="flex justify-center min-h-[260px]"></div>
        </flux:card>
    </div>
  </div>

  <script type="module">
    document.addEventListener("DOMContentLoaded", function () {
      if (typeof ApexCharts !== 'undefined') {
        // Monthly Chart
        const monthlyData = @json($monthlyStats);
        if (monthlyData && monthlyData.series) {
            new ApexCharts(document.getElementById("monthlyChart"), {
                series: [{ name: 'Arsip Diunggah', data: monthlyData.series }],
                chart: { type: 'bar', height: 120, toolbar: { show: false }, sparkline: { enabled: true } },
                plotOptions: { bar: { borderRadius: 4, horizontal: false } },
                dataLabels: { enabled: false },
                xaxis: { categories: monthlyData.labels },
                colors: ['#465FFF']
            }).render();
        }

        // Category Chart
        const catData = @json($categoryStats);
        if (catData && catData.series && catData.series.length > 0) {
            new ApexCharts(document.getElementById("categoryChart"), {
                series: catData.series,
                labels: catData.labels,
                chart: { type: 'donut', height: 320 },
                dataLabels: { enabled: true },
                legend: { position: 'bottom' },
                colors: ['#465FFF', '#0CB8E5', '#10B981', '#F59E0B', '#EF4444']
            }).render();
        }

        // Classification Chart
        const classData = @json($classificationStats);
        if (classData && classData.series && classData.series.length > 0) {
            new ApexCharts(document.getElementById("classificationChart"), {
                series: classData.series,
                labels: classData.labels,
                chart: { type: 'pie', height: 320 },
                dataLabels: { enabled: true },
                legend: { position: 'bottom' },
                colors: ['#10B981', '#3B82F6', '#F59E0B', '#EF4444']
            }).render();
        }
      }
    });
  </script>
@endsection
