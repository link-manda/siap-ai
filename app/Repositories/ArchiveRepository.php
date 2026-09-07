<?php

namespace App\Repositories;

use App\Interfaces\ArchiveRepositoryInterface;
use App\Models\Archive;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ArchiveRepository implements ArchiveRepositoryInterface
{
    public function getAllPaginated(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $query = $this->getBaseQuery()->with(['category', 'classification', 'uploader']);

        if (! empty($filters['category_id'])) {
            $query->where('archives.category_id', $filters['category_id']);
        }

        if (! empty($filters['classification_id'])) {
            $query->where('archives.classification_id', $filters['classification_id']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $startDate = Carbon::parse($filters['start_date'])->startOfDay();
            $endDate = Carbon::parse($filters['end_date'])->endOfDay();
            $query->whereBetween('archives.created_at', [$startDate, $endDate]);
        }

        return $query->latest('archives.created_at')->paginate($perPage);
    }

    public function findById(int $id): ?Archive
    {
        return Archive::with(['category', 'classification', 'uploader', 'logs.user'])->find($id);
    }

    public function create(array $data): Archive
    {
        return Archive::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $archive = Archive::findOrFail($id);

        return $archive->update($data);
    }

    public function delete(int $id): bool
    {
        $archive = Archive::findOrFail($id);

        return $archive->delete();
    }

    protected function getBaseQuery()
    {
        $query = Archive::query();
        $user = auth()->user();

        if ($user && ! $user->hasRole('Pimpinan')) {
            $query->whereHas('classification', function ($q) {
                $q->where('level', '<=', 3);
            });
        }

        return $query;
    }

    public function countTotal(): int
    {
        return $this->getBaseQuery()->count();
    }

    public function getCategoryStats(): array
    {
        $stats = $this->getBaseQuery()
            ->join('categories', 'archives.category_id', '=', 'categories.id')
            ->whereNull('categories.deleted_at')
            ->selectRaw('categories.name as category, COUNT(archives.id) as total')
            ->groupBy('categories.id', 'categories.name')
            ->get();

        $total = $stats->sum('total');
        $items = $stats->map(function ($item) use ($total) {
            return [
                'name' => $item->category,
                'total' => (int) $item->total,
                'percentage' => $total > 0 ? round(($item->total / $total) * 100, 1) : 0,
            ];
        })->sortByDesc('total')->values()->take(5)->toArray();

        return [
            'labels' => $stats->pluck('category')->toArray(),
            'series' => $stats->pluck('total')->toArray(),
            'items' => $items,
        ];
    }

    public function getClassificationStats(): array
    {
        $stats = $this->getBaseQuery()
            ->join('classifications', 'archives.classification_id', '=', 'classifications.id')
            ->selectRaw('classifications.name as classification, COUNT(archives.id) as total')
            ->groupBy('classifications.id', 'classifications.name')
            ->get();

        return [
            'labels' => $stats->pluck('classification')->toArray(),
            'series' => $stats->pluck('total')->toArray(),
        ];
    }

    public function getMonthlyStats(): array
    {
        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', archives.created_at) AS INTEGER)"
            : 'MONTH(archives.created_at)';

        $stats = $this->getBaseQuery()
            ->whereYear('archives.created_at', date('Y'))
            ->selectRaw("{$monthExpression} as month, COUNT(archives.id) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')->toArray();

        $series = [];
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        for ($i = 1; $i <= 12; $i++) {
            $series[] = $stats[$i] ?? 0;
        }

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    public function getStorageStats(): array
    {
        $today = Carbon::today();

        $activeSize = (int) $this->getBaseQuery()
            ->where(function ($query) use ($today) {
                $query->whereNull('retention_date')
                    ->orWhere('retention_date', '>=', $today);
            })
            ->sum('file_size');

        $expiredSize = (int) $this->getBaseQuery()
            ->whereNotNull('retention_date')
            ->where('retention_date', '<', $today)
            ->sum('file_size');

        $trashSize = (int) Archive::onlyTrashed()->sum('file_size');

        return [
            'active_size' => $activeSize,
            'expired_size' => $expiredSize,
            'trash_size' => $trashSize,
        ];
    }

    public function getAiStats(): array
    {
        $baseQuery = $this->getBaseQuery();

        $avgScore = $baseQuery->clone()->whereNotNull('ai_confidence_score')->avg('ai_confidence_score');
        $lowConfidenceCount = $baseQuery->clone()->whereNotNull('ai_confidence_score')->where('ai_confidence_score', '<', 70)->count();
        $totalProcessed = $baseQuery->clone()->whereNotNull('ai_confidence_score')->count();

        return [
            'average_confidence' => $avgScore ? (int) round((float) $avgScore) : 0,
            'low_confidence_count' => (int) $lowConfidenceCount,
            'total_ai_processed' => (int) $totalProcessed,
        ];
    }

    public function getAllForExport(array $filters = []): Collection
    {
        $query = $this->getBaseQuery()->with(['category', 'classification', 'uploader']);

        if (! empty($filters['category_id'])) {
            $query->where('archives.category_id', $filters['category_id']);
        }

        if (! empty($filters['classification_id'])) {
            $query->where('archives.classification_id', $filters['classification_id']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $startDate = Carbon::parse($filters['start_date'])->startOfDay();
            $endDate = Carbon::parse($filters['end_date'])->endOfDay();
            $query->whereBetween('archives.created_at', [$startDate, $endDate]);
        }

        return $query->latest('archives.created_at')->get();
    }
}
