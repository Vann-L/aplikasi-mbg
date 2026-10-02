@extends('layouts.app')

@section('title', 'Detail Jadwal')
@section('subtitle', 'Jadwal kerja pegawai SPPG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Detail Jadwal">
            <dl class="space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Pegawai</dt>
                    <dd class="font-medium text-slate-800">{{ $jadwal->pegawai->nama }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Tanggal</dt>
                    <dd class="text-slate-800">{{ $jadwal->tanggal->format('d/m/Y') }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Jam Masuk</dt>
                    <dd class="text-slate-800">{{ $jadwal->jam_masuk?->format('H:i') ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Jam Pulang</dt>
                    <dd class="text-slate-800">{{ $jadwal->jam_pulang?->format('H:i') ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    <dd>@include('jadwal._status_badge', ['status' => $jadwal->status])</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Catatan</dt>
                    <dd class="text-slate-800">{{ $jadwal->catatan ?: '-' }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex items-center gap-4">
                <a href="{{ route($routePrefix . '.jadwal.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    Kembali
                </a>

                @if ($canManage)
                    <a href="{{ route($routePrefix . '.jadwal.edit', $jadwal) }}"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Edit
                    </a>
                @endif
            </div>

            @if ($hasAbsensi)
                <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                    Jadwal ini sudah memiliki absensi. Pegawai, tanggal, jam, dan status tidak dapat diubah; hanya catatan
                    yang boleh diperbarui dan jadwal tidak dapat dihapus.
                </div>
            @endif
        </x-card>
    </div>
@endsection