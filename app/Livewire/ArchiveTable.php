<?php

namespace App\Livewire;

use App\Models\Archive;
use App\Models\Category;
use App\Models\Classification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ArchiveTable extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public string $categoryId = '';

    public string $classificationId = '';

    #[Url]
    public string $filter = '';

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

    public function updatingFilter(): void
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

    public array $selected = [];

    public bool $selectAll = false;

    public string $deleteReason = '';

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $query = Archive::latest();

            $user = Auth::user();
            if ($user && ! $user->hasRole('Pimpinan')) {
                $query->whereHas('classification', function ($q) {
                    $q->where('level', '<=', 3);
                });
            }

            if ($this->filter === 'expired') {
                $query->whereDate('retention_date', '<', now());
            } elseif ($this->filter === 'low_confidence') {
                $query->whereNotNull('ai_confidence_score')->where('ai_confidence_score', '<', 70);
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

            $this->selected = $query->paginate($this->perPage)->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function confirmDelete($id = null): void
    {
        if ($id) {
            $this->selected = [$id];
        }

        if (empty($this->selected)) {
            $this->dispatch('toast-show', slots: ['text' => 'Pilih minimal satu arsip untuk dihapus.'], dataset: ['variant' => 'warning']);

            return;
        }

        $this->deleteReason = '';
        \Flux::modal('delete-reason-modal')->show();
    }

    public function executeDelete(): void
    {

        if (empty($this->selected)) {
            return;
        }

        // Validasi Alasan
        if (empty(trim($this->deleteReason))) {
            $this->addError('deleteReason', 'Alasan penghapusan wajib diisi.');

            return;
        }

        $archives = Archive::whereIn('id', $this->selected)->get();

        foreach ($archives as $archive) {
            $this->authorize('delete', $archive);
            $archive->delete_reason = trim($this->deleteReason);
            $archive->save();
            $archive->delete();
        }

        $this->selected = [];
        $this->deleteReason = '';
        \Flux::modal('delete-reason-modal')->close();
        $this->dispatch('toast-show', slots: ['text' => 'Arsip berhasil dihapus beserta alasannya!', 'heading' => 'Sukses'], dataset: ['variant' => 'success']);
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

        if ($this->filter === 'expired') {
            $query->whereDate('retention_date', '<', now());
        } elseif ($this->filter === 'low_confidence') {
            $query->whereNotNull('ai_confidence_score')->where('ai_confidence_score', '<', 70);
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
