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

        if (Auth::id() === $user->id) {
            session()->flash('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            $this->dispatch('toast-show', slots: ['text' => 'Anda tidak dapat menghapus akun Anda sendiri.', 'heading' => 'Kesalahan'], dataset: ['variant' => 'danger'], duration: 0);

            return;
        }

        $this->authorize('delete', $user);

        $user->delete();
        session()->flash('success', 'Pengguna berhasil dihapus.');
        $this->dispatch('toast-show', slots: ['text' => 'Pengguna berhasil dihapus.', 'heading' => 'Sukses'], dataset: ['variant' => 'success'], duration: 5000);
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
