@extends('layouts.app')

@section('title', 'Reset Password Akun')
@section('subtitle', 'Kelola akun pengguna dan keterkaitannya dengan pegawai')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Reset Password">
            <p class="mb-2 text-sm text-slate-600">
                Reset password untuk akun <strong>{{ $user->name }}</strong> ({{ $user->email }}).
                Password baru tidak dapat dikembalikan ke kondisi sebelumnya.
            </p>

            <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700">Password Baru</label>
                        <input id="password" name="password" type="password" required
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        <p class="mt-1 text-xs text-slate-500">Minimal 8 karakter.</p>
                        @error('password')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Konfirmasi Password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Reset Password
                    </button>
                    <a href="{{ route('admin.user.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
                </div>
            </form>
        </x-card>
    </div>
@endsection