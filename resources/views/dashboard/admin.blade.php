@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan data SPPG')

@section('content')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="Total Pegawai" :value="$totalPegawai" />
        <x-stat-card label="Total Sekolah" :value="$totalSekolah" />
        <x-stat-card label="Total Menu" :value="$totalMenu" />
        <x-stat-card label="Total Bahan Baku" :value="$totalBahanBaku" />
        <x-stat-card label="Distribusi Hari Ini" :value="$distribusiHariIni" />
    </div>

    @if ($totalPegawai + $totalSekolah + $totalMenu + $totalBahanBaku === 0)
        <div class="mt-6">
            <x-empty-state
                title="Belum ada data master"
                description="Tambahkan pegawai, sekolah, menu, dan bahan baku untuk mulai mengelola SPPG."
            />
        </div>
    @endif
@endsection
