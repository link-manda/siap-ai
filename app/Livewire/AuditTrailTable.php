<?php

namespace App\Livewire;

use App\Models\ArchiveLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class AuditTrailTable extends Component
{
    use WithPagination;

    public string $tab = 'document';

    public string $search = '';

    public int $perPage = 15;

    protected $queryString = [
        'tab' => ['except' => 'document'],
        'search' => ['except' => ''],
    ];

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['document', 'system']) ? $tab : 'document';
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();

        if ($this->tab === 'system') {
            $systemQuery = Activity::with(['causer', 'subject'])->latest();

            if (! empty($this->search)) {
                $systemQuery->where(function ($q) {
                    $q->where('event', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhereHasMorph('causer', [User::class], function ($sq) {
                            $sq->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            }

            $systemLogs = $systemQuery->paginate($this->perPage);
            $documentLogs = null;
        } else {
            $docQuery = ArchiveLog::with(['user', 'archive.classification'])->latest();

            if ($user && ! $user->hasRole('Pimpinan')) {
                $docQuery->whereHas('archive.classification', function ($q) {
                    $q->where('level', '<=', 3);
                });
            }

            if (! empty($this->search)) {
                $docQuery->where(function ($q) {
                    $q->where('action', 'like', '%'.$this->search.'%')
                        ->orWhereHas('archive', function ($sq) {
                            $sq->where('title', 'like', '%'.$this->search.'%');
                        })
                        ->orWhereHas('user', function ($sq) {
                            $sq->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            }

            $documentLogs = $docQuery->paginate($this->perPage);
            $systemLogs = null;
        }

        return view('livewire.audit-trail-table', [
            'documentLogs' => $documentLogs,
            'systemLogs' => $systemLogs,
        ]);
    }
}
