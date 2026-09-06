<?php

namespace App\Livewire;

use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ArchiveTable extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $categoryId = '';

    public string $classificationId = '';

    public string $startDate = '';

    public string $endDate = '';

    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['except' => ''],
        'classificationId' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatingClassificationId(): void
    {
        $this->resetPage();
    }

    public function updatingStartDate(): void
    {
        $this->resetPage();
    }

    public function updatingEndDate(): void
    {
        $this->resetPage();
    }

    public function deleteArchive(int $id): void
    {
        $archive = Archive::findOrFail($id);
        $this->authorize('delete', $archive);

        $archive->delete();
        session()->flash('success', 'Arsip berhasil dihapus!');
    }

    public function render()
    {
        $query = Archive::with(['category', 'classification', 'uploader'])->latest();

        $user = Auth::user();
        if ($user && ! $user->hasRole('Pimpinan')) {
            $query->whereHas('classification', function ($q) {
                $q->where('level', '<=', 3);
            });
        }

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('document_number', 'like', '%'.$this->search.'%');
            });
        }

        if (! empty($this->categoryId)) {
            $query->where('category_id', $this->categoryId);
        }

        if (! empty($this->classificationId)) {
            $query->where('classification_id', $this->classificationId);
        }

        if (! empty($this->startDate) && ! empty($this->endDate)) {
            $query->whereBetween('created_at', [
                $this->startDate.' 00:00:00',
                $this->endDate.' 23:59:59',
            ]);
        }

        $archives = $query->paginate($this->perPage);

        $categories = Category::all();
        $classifications = ($user && $user->hasRole('Pimpinan'))
            ? Classification::all()
            : Classification::where('level', '<=', 3)->get();

        return view('livewire.archive-table', [
            'archives' => $archives,
            'categories' => $categories,
            'classifications' => $classifications,
        ]);
    }
}
