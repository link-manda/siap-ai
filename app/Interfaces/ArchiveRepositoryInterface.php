<?php

namespace App\Interfaces;

use App\Models\Archive;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ArchiveRepositoryInterface
{
    public function getAllPaginated(int $perPage = 10, array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?Archive;

    public function create(array $data): Archive;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function countTotal(): int;

    public function getCategoryStats(): array;

    public function getClassificationStats(): array;

    public function getMonthlyStats(): array;

    public function getStorageStats(): array;

    public function getAllForExport(array $filters = []): Collection;
}
