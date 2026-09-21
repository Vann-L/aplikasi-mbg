@extends('layouts.app')

@section('title', 'Detail Pegawai')
@section('subtitle', 'Master data pegawai SPPG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Detail Pegawai">
            <dl class="space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">ID Pegawai</dt>
                    <dd class="font-medium text-slate-800">{{ $pegawai->id_pegawai }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Nama</dt>
                    <dd class="font-medium text-slate-800">{{ $pegawai->nama }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Jabatan</dt>
                    <dd class="text-slate-800">{{ $pegawai->jabatan ?: '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Bagian</dt>
                    <dd class="text-slate-800">{{ $pegawai->bagian ?: '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">No. HP</dt>
                    <dd class="text-slate-800">{{ $pegawai->no_hp ?: '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    <dd>
                        <span
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $pegawai->status === \App\Models\Pegawai::STATUS_ACTIVE ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $pegawai->status === \App\Models\Pegawai::STATUS_ACTIVE ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Akun</dt>
                    <dd class="text-slate-800">{{ $pegawai->user?->name ?? 'Belum terhubung' }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex items-center gap-4">
                <a href="{{ route('admin.pegawai.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    Kembali
                </a>

                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.pegawai.edit', $pegawai) }}"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Edit
                    </a>
                @endif
            </div>
        </x-card>
    </div>
@endsection
