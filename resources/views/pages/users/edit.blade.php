@extends('layouts.app')

@section('content')
<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            {{ $title }}
        </h3>
    </div>
    <form action="{{ route('users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="p-6.5">
            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">Nama</label>
                <input type="text" name="name" value="{{ $user->name }}" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary" required>
            </div>
            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">Email</label>
                <input type="email" name="email" value="{{ $user->email }}" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary" required>
            </div>
            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">Password Baru (Biarkan kosong jika tidak diubah)</label>
                <input type="password" name="password" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
            </div>
            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-primary active:border-primary dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary">
            </div>
            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">Role</label>
                <div class="flex flex-col gap-2">
                    @foreach($roles as $role)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="form-checkbox h-5 w-5 text-primary" @if($user->hasRole($role->name)) checked @endif>
                            <span>{{ $role->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <button class="flex w-full justify-center rounded bg-primary p-3 font-medium text-white hover:bg-opacity-90">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection
