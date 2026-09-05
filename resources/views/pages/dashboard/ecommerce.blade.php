@extends('layouts.app')

@section('content')
  <div class="grid grid-cols-12 gap-4 md:gap-6">
    <div class="col-span-12">
        <h2 class="text-title-sm font-semibold text-gray-800 dark:text-white/90 mb-4">Ringkasan Sistem</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Arsip</p>
                        <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">{{ $totalArchives ?? 0 }}</h4>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:col-span-2">
                 <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Tren Unggahan Bulanan</h4>
                 <div id="monthlyChart" class="-ml-4 -mb-4"></div>
            </div>
        </div>
    </div>
    
    <div class="col-span-12 md:col-span-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark h-full">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-6 text-center">Komposisi Kategori</h4>
            <div id="categoryChart" class="flex justify-center"></div>
        </div>
    </div>
    
    <div class="col-span-12 md:col-span-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark h-full">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-6 text-center">Klasifikasi Kerahasiaan</h4>
            <div id="classificationChart" class="flex justify-center"></div>
        </div>
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
