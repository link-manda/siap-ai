<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';

    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public ?int $userToDeleteId = null;

    public string $userToDeleteName = '';

    public function confirmDeleteUser(int $id, string $name): void
    {
        $this->userToDeleteId = $id;
        $this->userToDeleteName = $name;
        \Flux::modal('delete-user-modal')->show();
    }

    public function executeDeleteUser(): void
    {
        if (! $this->userToDeleteId) {
            return;
        }

        $user = User::findOrFail($this->userToDeleteId);

        if (Auth::id() === $user->id) {
            $this->dispatch('toast-show', slots: ['text' => 'Anda tidak dapat menghapus akun Anda sendiri.', 'heading' => 'Kesalahan'], dataset: ['variant' => 'danger']);
            \Flux::modal('delete-user-modal')->close();

            return;
        }

        $this->authorize('delete', $user);

        $user->delete();
        $this->dispatch('toast-show', slots: ['text' => 'Pengguna berhasil dihapus.', 'heading' => 'Sukses'], dataset: ['variant' => 'success']);

        $this->userToDeleteId = null;
        $this->userToDeleteName = '';
        \Flux::modal('delete-user-modal')->close();
    }

    public function render()
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('roles')->latest();

        if (! empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        $users = $query->paginate($this->perPage);

        return view('livewire.user-table', [
            'users' => $users,
        ]);
    }
}
