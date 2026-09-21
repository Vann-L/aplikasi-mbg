@extends('layouts.app')

@section('title', 'Detail Bahan Baku')
@section('subtitle', 'Master data bahan baku SPPG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Detail Bahan Baku">
            <dl class="space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Nama Bahan</dt>
                    <dd class="font-medium text-slate-800">{{ $bahanBaku->nama }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Satuan</dt>
                    <dd class="text-slate-800">{{ $bahanBaku->satuan }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Stok Minimum</dt>
                    <dd class="font-semibold text-slate-800">
                        {{ rtrim(rtrim(number_format((float) $bahanBaku->stok_minimum, 2, ',', '.'), '0'), ',') }}
                        {{ $bahanBaku->satuan }}
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    <dd>
                        <span
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $bahanBaku->status === \App\Models\BahanBaku::STATUS_ACTIVE ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $bahanBaku->status === \App\Models\BahanBaku::STATUS_ACTIVE ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Pemakaian Produksi</dt>
                    <dd class="text-slate-800">{{ $bahanBaku->produksiBahans()->count() }} record</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Mutasi Stok</dt>
                    <dd class="text-slate-800">{{ $bahanBaku->stokMutations()->count() }} record</dd>
                </div>
            </dl>

            <p class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                Stok minimum dipakai sebagai indikator stok rendah. Perhitungan stok tersedia dibuat pada Phase 5.
            </p>

            <div class="mt-6 flex items-center gap-4">
                <a href="{{ route('admin.bahan-baku.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    Kembali
                </a>

                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.bahan-baku.edit', $bahanBaku) }}"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Edit
                    </a>
                @endif
            </div>
        </x-card>
    </div>
@endsection
