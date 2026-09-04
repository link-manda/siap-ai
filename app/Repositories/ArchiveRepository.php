<?php

namespace App\Repositories;

use App\Interfaces\ArchiveRepositoryInterface;
use App\Models\Archive;
use Illuminate\Pagination\LengthAwarePaginator;

class ArchiveRepository implements ArchiveRepositoryInterface
{
    public function getAllPaginated(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $query = Archive::with(['category', 'classification', 'uploader']);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }
        
        $user = auth()->user();
        if ($user && $user->role === 'Admin') {
            $query->whereHas('classification', function ($q) {
                $q->where('level', '<=', 3);
            });
        }

        return $query->latest()->paginate($perPage);
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

    public function countTotal(): int
    {
        $query = Archive::query();
        $user = auth()->user();
        
        if ($user && $user->role === 'Admin') {
            $query->whereHas('classification', function ($q) {
                $q->where('level', '<=', 3);
            });
        }
        
        return $query->count();
    }
}
