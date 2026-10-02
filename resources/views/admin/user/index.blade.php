@extends('layouts.app')

@section('title', 'Pengguna')
@section('subtitle', 'Kelola akun pengguna dan keterkaitannya dengan pegawai')

@section('content')
    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route('admin.user.index') }}" class="flex flex-col gap-2 sm:flex-row">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama / email / ID pegawai"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 sm:w-64">

                <select name="role"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Semua role</option>
                    <option value="admin" @selected($role === 'admin')>Admin</option>
                    <option value="kepala" @selected($role === 'kepala')>Kepala</option>
                    <option value="petugas" @selected($role === 'petugas')>Petugas</option>
                </select>

                <select name="status"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Semua status</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                </select>

                <button type="submit"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Cari
                </button>
            </form>

            <a href="{{ route('admin.user.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                Tambah Akun
            </a>
        </div>

        <x-card>
            @if ($users->isEmpty())
                <x-empty-state title="Belum ada akun pengguna"
                    description="Belum ada akun pengguna. Tambahkan akun untuk pegawai agar dapat mengakses aplikasi." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Nama</th>
                                <th class="px-3 py-2 font-medium">Email</th>
                                <th class="px-3 py-2 font-medium">Pegawai</th>
                                <th class="px-3 py-2 font-medium">Role</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                                <th class="px-3 py-2 font-medium">Last Login</th>
                                <th class="px-3 py-2 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $item)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $item->name }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->email }}</td>
                                    <td class="px-3 py-2 text-slate-600">
                                        @if ($item->pegawai)
                                            <span class="font-medium text-slate-700">{{ $item->pegawai->nama }}</span>
                                            <span class="text-xs text-slate-500">({{ $item->pegawai->id_pegawai }})</span>
                                        @else
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize bg-emerald-50 text-emerald-700">
                                            {{ $item->role }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $item->status === \App\Models\User::STATUS_ACTIVE ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $item->status === \App\Models\User::STATUS_ACTIVE ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->last_login_at?->format('d/m/Y H:i') ?: '-' }}</td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('admin.user.edit', $item) }}"
                                                class="text-emerald-700 hover:text-emerald-800">Edit</a>
                                            <a href="{{ route('admin.users.reset', $item) }}"
                                                class="text-slate-600 hover:text-slate-900">Reset Password</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection