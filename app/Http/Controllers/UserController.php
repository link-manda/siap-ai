<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected UserRepository $userRepository
    ) {}

    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = $this->userRepository->getAllPaginated(15);

        return view('pages.users.index', [
            'title' => 'Manajemen Pengguna',
            'users' => $users,
        ]);
    }

    public function create()
    {
        $this->authorize('create', User::class);
        $roles = Role::all();

        return view('pages.users.create', [
            'title' => 'Tambah Pengguna',
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $this->userService->createUser($validated, $request->input('roles', []));

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $user = $this->userRepository->findById($id);
        $this->authorize('update', $user);
        $roles = Role::all();

        return view('pages.users.edit', [
            'title' => 'Edit Pengguna',
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $this->userRepository->findById($id);
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
        ]);

        $this->userService->updateUser($user, $validated, $request->input('roles', []));

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = $this->userRepository->findById($id);
        $this->authorize('delete', $user);

        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        $this->userRepository->delete($user);

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
