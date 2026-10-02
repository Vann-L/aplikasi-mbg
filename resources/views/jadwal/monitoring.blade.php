@extends('layouts.app')

@section('title', 'Monitoring Jadwal')
@section('subtitle', 'Matriks absensi mingguan pegawai')

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

        <div class="no-print">
            @include('jadwal._nav', ['routePrefix' => $routePrefix, 'active' => $active])
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route($routePrefix . '.jadwal.monitoring') }}"
                class="flex items-center gap-2">
                <input type="date" name="tanggal" value="{{ $tanggal }}"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <button type="submit"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Pilih Minggu
                </button>
            </form>

            <div class="flex items-center gap-2">
                <a href="{{ route($routePrefix . '.jadwal.monitoring', ['tanggal' => $mulai->subWeek()->toDateString()]) }}"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100">
                    &larr; Minggu lalu
                </a>
                <a href="{{ route($routePrefix . '.jadwal.monitoring', ['tanggal' => $mulai->addWeek()->toDateString()]) }}"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100">
                    Minggu depan &rarr;
                </a>
            </div>
        </div>

        <x-card>
            <p class="mb-3 text-xs text-slate-500">
                Minggu {{ $mulai->format('d/m/Y') }} &ndash; {{ $selesai->format('d/m/Y') }}. Status diturunkan dari
                jadwal efektif (kalender operasional, override tanggal, dan pola mingguan).
            </p>

            <div class="mb-3 flex flex-wrap gap-3 text-xs text-slate-500">
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Hadir</span>
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-amber-500"></span>Terlambat</span>
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-red-500"></span>Alpa</span>
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-sky-500"></span>Izin</span>
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-slate-400"></span>Libur</span>
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full bg-slate-200"></span>Belum Absen</span>
                <span><span class="mr-1 inline-block h-2.5 w-2.5 rounded-full border border-dashed border-slate-300"></span>Tanpa Jadwal</span>
            </div>

            @if ($rows->isEmpty())
                <x-empty-state title="Belum ada pegawai aktif"
                    description="Tambahkan pegawai aktif terlebih dahulu agar dapat dipantau." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Pegawai</th>
                                @foreach ($rows->first()['hari'] as $hari)
                                    <th class="px-3 py-2 text-center font-medium">
                                        <span class="block">{{ $hari['tanggal']->isoFormat('ddd') }}</span>
                                        <span class="block text-[10px] font-normal text-slate-400">{{ $hari['tanggal']->format('d/m') }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($rows as $baris)
                                <tr>
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-slate-700">{{ $baris['pegawai']->nama }}</p>
                                        <p class="text-xs text-slate-500">{{ $baris['pegawai']->jabatan ?? '-' }}</p>
                                    </td>
                                    @foreach ($baris['hari'] as $hari)
                                        @php
                                            $warna = [
                                                \App\Models\Absensi::STATUS_HADIR => 'bg-emerald-100 text-emerald-700',
                                                \App\Models\Absensi::STATUS_TERLAMBAT => 'bg-amber-100 text-amber-700',
                                                \App\Models\Absensi::STATUS_ALPA => 'bg-red-100 text-red-700',
                                                \App\Models\Absensi::STATUS_IZIN => 'bg-sky-100 text-sky-700',
                                                \App\Models\JadwalPegawai::STATUS_LIBUR => 'bg-slate-200 text-slate-600',
                                                'belum' => 'bg-slate-100 text-slate-400',
                                            ];
                                            $kolom = $warna[$hari['status']] ?? 'bg-white text-slate-300';
                                            $label = [
                                                \App\Models\Absensi::STATUS_HADIR => 'H',
                                                \App\Models\Absensi::STATUS_TERLAMBAT => 'T',
                                                \App\Models\Absensi::STATUS_ALPA => 'A',
                                                \App\Models\Absensi::STATUS_IZIN => 'I',
                                                \App\Models\JadwalPegawai::STATUS_LIBUR => 'L',
                                                'belum' => 'B',
                                            ][$hari['status']] ?? '-';
                                        @endphp
                                        <td class="px-2 py-2 text-center">
                                            <span title="{{ $hari['status'] }}" class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold {{ $kolom }}">
                                                {{ $label }}
                                            </span>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>
@endsection