@extends('layouts.app')

@section('title', 'Pegawai')
@section('subtitle', 'Master data pegawai SPPG')

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
            <form method="GET" action="{{ route('admin.pegawai.index') }}" class="flex flex-col gap-2 sm:flex-row">
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama / ID / jabatan"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 sm:w-64">

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

            @if (auth()->user()->role === 'admin')
                <a href="{{ route('admin.pegawai.create') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Tambah Pegawai
                </a>
            @endif
        </div>

        <x-card>
            @if ($pegawai->isEmpty())
                <x-empty-state title="Belum ada pegawai"
                    description="Data pegawai belum tersedia. Tambahkan pegawai baru untuk memulai." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">ID Pegawai</th>
                                <th class="px-3 py-2 font-medium">Nama</th>
                                <th class="px-3 py-2 font-medium">Jabatan</th>
                                <th class="px-3 py-2 font-medium">Bagian</th>
                                <th class="px-3 py-2 font-medium">No. HP</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                                <th class="px-3 py-2 font-medium">Akun</th>
                                <th class="px-3 py-2 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($pegawai as $item)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $item->id_pegawai }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ $item->nama }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->jabatan ?: '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->bagian ?: '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->no_hp ?: '-' }}</td>
                                    <td class="px-3 py-2">
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $item->status === \App\Models\Pegawai::STATUS_ACTIVE ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                            {{ $item->status === \App\Models\Pegawai::STATUS_ACTIVE ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->user?->name ?? 'Belum terhubung' }}</td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('admin.pegawai.show', $item) }}"
                                                class="text-slate-600 hover:text-slate-900">Detail</a>

                                            @if (auth()->user()->role === 'admin')
                                                <a href="{{ route('admin.pegawai.edit', $item) }}"
                                                    class="text-emerald-700 hover:text-emerald-800">Edit</a>

                                                <form method="POST" action="{{ route('admin.pegawai.destroy', $item) }}"
                                                    onsubmit="return confirm('Hapus pegawai ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-700">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $pegawai->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection
