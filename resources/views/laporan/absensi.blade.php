@extends('layouts.app')

@section('title', 'Laporan Absensi')
@section('subtitle', 'Rekap jadwal dan absensi pegawai')

@section('content')
    <div class="space-y-4">
        <div class="no-print">
            @include('laporan._nav', ['routePrefix' => $routePrefix, 'active' => $active])
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route($routePrefix . '.laporan.absensi') }}"
                class="no-print flex flex-col gap-2 sm:flex-row">
                <input type="date" name="tanggal_awal" value="{{ $tanggalAwal }}"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <input type="date" name="tanggal_akhir" value="{{ $tanggalAkhir }}"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <select name="pegawai"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Semua pegawai</option>
                    @foreach ($pegawaiList as $item)
                        <option value="{{ $item->id }}" @selected($pegawaiId === $item->id)>{{ $item->nama }}</option>
                    @endforeach
                </select>
                <select name="status"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Semua status</option>
                    @foreach ($availableStatuses as $item)
                        <option value="{{ $item }}" @selected($status === $item)>{{ ucwords(str_replace('_', ' ', $item)) }}</option>
                    @endforeach
                </select>
                <button type="submit"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Cari
                </button>
                <a href="{{ route($routePrefix . '.laporan.absensi') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Reset
                </a>
            </form>

            <button type="button" onclick="window.print()"
                class="no-print inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                Print
            </button>
        </div>

        <div class="hidden print:block">
            <h1 class="text-lg font-bold text-slate-800">Laporan Absensi</h1>
            <p class="text-xs text-slate-500">
                Periode: {{ $tanggalAwal ?: 'Semua' }} &ndash; {{ $tanggalAkhir ?: 'Semua' }}
                &middot; Pegawai: {{ $pegawaiList->firstWhere('id', $pegawaiId)?->nama ?? 'Semua' }}
                &middot; Status: {{ $status ?: 'Semua' }}
            </p>
        </div>

        <x-card>
            @if ($jadwal->isEmpty())
                <x-empty-state title="Belum ada data absensi"
                    description="Tidak ada jadwal pegawai yang cocok dengan filter yang dipilih." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Tanggal</th>
                                <th class="px-3 py-2 font-medium">Pegawai</th>
                                <th class="px-3 py-2 font-medium">Jadwal Masuk</th>
                                <th class="px-3 py-2 font-medium">Jadwal Pulang</th>
                                <th class="px-3 py-2 font-medium">Jam Masuk</th>
                                <th class="px-3 py-2 font-medium">Jam Pulang</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($jadwal as $item)
                                @php
                                    $absensi = $item->pegawai?->absensi->firstWhere(fn ($a) => $a->tanggal?->toDateString() === $item->tanggal?->toDateString());
                                    $jadwalAktif = $item->status === \App\Models\JadwalPegawai::STATUS_TERJADWAL;
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->tanggal->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-slate-700">{{ $item->pegawai?->nama ?? '-' }}</p>
                                        <p class="text-xs text-slate-500">{{ $item->pegawai?->jabatan ?? '-' }} &middot; {{ $item->pegawai?->bagian ?? '-' }}</p>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ $jadwalAktif ? ($item->jam_masuk?->format('H:i') ?? '-') : '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $jadwalAktif ? ($item->jam_pulang?->format('H:i') ?? '-') : '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $absensi?->jam_masuk?->format('H:i') ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $absensi?->jam_pulang?->format('H:i') ?? '-' }}</td>
                                    <td class="px-3 py-2">@include('absensi._status_badge', ['status' => $item->statusRekap()])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $jadwal->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection