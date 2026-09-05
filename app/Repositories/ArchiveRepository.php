<?php

namespace App\Repositories;

use App\Interfaces\ArchiveRepositoryInterface;
use App\Models\Archive;
use Illuminate\Pagination\LengthAwarePaginator;

class ArchiveRepository implements ArchiveRepositoryInterface
{
    public function getAllPaginated(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $query = $this->getBaseQuery()->with(['category', 'classification', 'uploader']);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('archives.created_at', [$filters['start_date'], $filters['end_date']]);
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
        
        if ($user && $user->role === 'Admin') {
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
            ->selectRaw('categories.name as category, COUNT(archives.id) as total')
            ->groupBy('categories.id', 'categories.name')
            ->get();
            
        return [
            'labels' => $stats->pluck('category')->toArray(),
            'series' => $stats->pluck('total')->toArray()
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
            'series' => $stats->pluck('total')->toArray()
        ];
    }
    
    public function getMonthlyStats(): array
    {
        $stats = $this->getBaseQuery()
            ->whereYear('archives.created_at', date('Y'))
            ->selectRaw('MONTH(archives.created_at) as month, COUNT(archives.id) as total')
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
            'series' => $series
        ];
    }
    
    public function getAllForExport(array $filters = []): \Illuminate\Support\Collection
    {
        $query = $this->getBaseQuery()->with(['category', 'classification', 'uploader']);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('archives.created_at', [$filters['start_date'], $filters['end_date']]);
        }
        
        return $query->latest('archives.created_at')->get();
    }
}
