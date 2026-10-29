<?php

namespace App\Livewire;

use App\Models\Archive;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ArchiveExplorer extends Component
{
    public string $groupingMode = 'category_classification';

    public string $activeNodeId = '';

    public int $selectedCount = 0;

    #[On('update-selected-count')]
    public function updateSelectedCount($count)
    {
        $this->selectedCount = $count;
    }

    public function selectNode($nodeId, $filters)
    {
        $this->activeNodeId = $nodeId;
        $this->dispatch('explorer-filter-updated', $filters);
    }

    public function resetSelection()
    {
        $this->activeNodeId = '';
        $this->dispatch('explorer-filter-updated', []);
    }

    #[Computed]
    public function smartFolders()
    {
        $query = Archive::with(['category', 'classification'])->latest();

        $user = Auth::user();
        if ($user && ! $user->hasRole('Pimpinan')) {
            $query->whereHas('classification', function ($q) {
                $q->where('level', '<=', 3);
            });
        }

        $archives = $query->get();
        $folders = [];

        if ($this->groupingMode === 'category_classification') {
            $grouped = $archives->groupBy('category.name');
            foreach ($grouped as $categoryName => $items) {
                $categoryName = $categoryName ?: 'Tanpa Kategori';
                $categoryId = $items->first()->category_id;

                $children = [];
                $subGrouped = $items->groupBy('classification.name');
                foreach ($subGrouped as $className => $subItems) {
                    $className = $className ?: 'Biasa';
                    $classId = $subItems->first()->classification_id;
                    $children[] = [
                        'id' => 'cat_'.$categoryId.'_class_'.$classId,
                        'name' => $className,
                        'count' => $subItems->count(),
                        'filters' => ['categoryId' => $categoryId, 'classificationId' => $classId],
                    ];
                }

                $folders[] = [
                    'id' => 'cat_'.$categoryId,
                    'name' => $categoryName,
                    'count' => $items->count(),
                    'filters' => ['categoryId' => $categoryId],
                    'children' => $children,
                ];
            }
        } elseif ($this->groupingMode === 'year_category') {
            $grouped = $archives->groupBy(function ($item) {
                return $item->created_at->format('Y');
            });

            foreach ($grouped as $year => $items) {
                $children = [];
                $subGrouped = $items->groupBy('category.name');
                foreach ($subGrouped as $categoryName => $subItems) {
                    $categoryName = $categoryName ?: 'Tanpa Kategori';
                    $categoryId = $subItems->first()->category_id;
                    $children[] = [
                        'id' => 'year_'.$year.'_cat_'.$categoryId,
                        'name' => $categoryName,
                        'count' => $subItems->count(),
                        'filters' => ['year' => $year, 'categoryId' => $categoryId],
                    ];
                }

                $folders[] = [
                    'id' => 'year_'.$year,
                    'name' => $year,
                    'count' => $items->count(),
                    'filters' => ['year' => $year],
                    'children' => $children,
                ];
            }
        }

        return collect($folders)->sortBy('name')->values()->toArray();
    }

    public function render()
    {
        return view('livewire.archive-explorer');
    }
}
