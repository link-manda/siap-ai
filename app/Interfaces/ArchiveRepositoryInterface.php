<?php

namespace App\Interfaces;

use App\Models\Archive;
use Illuminate\Pagination\LengthAwarePaginator;

interface ArchiveRepositoryInterface
{
    public function getAllPaginated(int $perPage = 10, array $filters = []): LengthAwarePaginator;
    
    public function findById(int $id): ?Archive;
    
    public function create(array $data): Archive;
    
    public function update(int $id, array $data): bool;
    
    public function delete(int $id): bool;
    
    public function countTotal(): int;
}
