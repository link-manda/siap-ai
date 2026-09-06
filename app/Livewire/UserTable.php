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

    public function deleteUser(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);

        if (Auth::id() === $user->id) {
            session()->flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');

            return;
        }

        $user->delete();
        session()->flash('success', 'Pengguna berhasil dihapus.');
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
