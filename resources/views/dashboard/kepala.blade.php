@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Monitoring operasional hari ini')

@section('content')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card
            label="Produksi Hari Ini"
            :value="$produksiHariIniCount.' batch'"
            :hint="'Target '.number_format($targetHariIni).' porsi'"
        />
        <x-stat-card
            label="Distribusi Hari Ini"
            :value="$distribusiHariIniCount"
            :hint="number_format($porsiDidistribusikan).' porsi'"
        />
        <x-stat-card
            label="Penerimaan Hari Ini"
            :value="$penerimaanHariIniCount"
            :hint="number_format($porsiDiterima).' porsi diterima'"
        />
        <x-stat-card
            label="Absensi Hari Ini"
            :value="$absensiHariIni.' / '.$totalPegawai"
            hint="Pegawai tercatat"
        />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Progress Produksi Hari Ini">
            <div class="flex items-end justify-between">
                <div>
                    <p class="text-3xl font-semibold text-slate-800">{{ $progressProduksi }}%</p>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ number_format($hasilHariIni) }} dari {{ number_format($targetHariIni) }} porsi
                    </p>
                </div>
                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                    {{ $produksiHariIniCount }} batch
                </span>
            </div>

            <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-emerald-500" style="width: {{ min($progressProduksi, 100) }}%"></div>
            </div>
        </x-card>

        <x-card title="Ringkasan Stok">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500">Total bahan baku</span>
                <span class="font-medium text-slate-800">{{ $totalBahanBaku }}</span>
            </div>

            @if ($bahanBakuDiBawahMinimum->isEmpty())
                <div class="mt-4">
                    <x-empty-state title="Stok aman" description="Tidak ada bahan baku di bawah stok minimum." />
                </div>
            @else
                <ul class="mt-4 divide-y divide-slate-100">
                    @foreach ($bahanBakuDiBawahMinimum as $bahan)
                        <li class="flex items-center justify-between py-2 text-sm">
                            <span class="text-slate-700">{{ $bahan['nama'] }}</span>
                            <span class="text-red-600">
                                {{ number_format($bahan['tersedia'], 2) }} / {{ number_format($bahan['minimum'], 2) }} {{ $bahan['satuan'] }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
@endsection
