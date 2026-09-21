@extends('layouts.app')

@section('title', 'Detail Sekolah')
@section('subtitle', 'Master data sekolah penerima MBG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Detail Sekolah">
            <dl class="space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">NPSN</dt>
                    <dd class="font-medium text-slate-800">{{ $sekolah->npsn }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Nama</dt>
                    <dd class="font-medium text-slate-800">{{ $sekolah->nama }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Alamat</dt>
                    <dd class="text-right text-slate-800">{{ $sekolah->alamat ?: '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Kontak</dt>
                    <dd class="text-slate-800">{{ $sekolah->kontak ?: '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Jumlah Penerima</dt>
                    <dd class="font-semibold text-slate-800">
                        {{ number_format($sekolah->jumlah_penerima, 0, ',', '.') }} penerima
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    <dd>
                        <span
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $sekolah->status === \App\Models\Sekolah::STATUS_ACTIVE ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $sekolah->status === \App\Models\Sekolah::STATUS_ACTIVE ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Distribusi Terkait</dt>
                    <dd class="text-slate-800">{{ $sekolah->distribusis()->count() }} distribusi</dd>
                </div>
            </dl>

            <p class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                Jumlah penerima adalah data referensi. Jumlah porsi aktual ditentukan pada modul Distribusi.
            </p>

            <div class="mt-6 flex items-center gap-4">
                <a href="{{ route('admin.sekolah.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    Kembali
                </a>

                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.sekolah.edit', $sekolah) }}"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Edit
                    </a>
                @endif
            </div>
        </x-card>
    </div>
@endsection
