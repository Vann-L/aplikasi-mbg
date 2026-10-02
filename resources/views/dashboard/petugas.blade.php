@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan tugas hari ini')

@section('content')
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Jadwal Kerja Hari Ini">
            @if ($jadwalEfektifHariIni === null)
                <x-empty-state title="Belum ada jadwal" description="Tidak ada jadwal kerja untuk hari ini." />
            @else
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Status</dt>
                        <dd>
                            @if ($jadwalEfektifHariIni->status === \App\Models\JadwalPegawai::STATUS_TERJADWAL)
                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Terjadwal</span>
                            @else
                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ ucfirst($jadwalEfektifHariIni->status) }}</span>
                            @endif
                        </dd>
                    </div>
                    @if ($jadwalEfektifHariIni->status === \App\Models\JadwalPegawai::STATUS_TERJADWAL)
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Jam Masuk</dt>
                            <dd class="font-medium text-slate-800">{{ $jadwalEfektifHariIni->jamMasuk?->format('H:i') ?? '-' }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Jam Pulang</dt>
                            <dd class="font-medium text-slate-800">{{ $jadwalEfektifHariIni->jamPulang?->format('H:i') ?? '-' }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-t border-slate-100 pt-2">
                        <dt class="text-slate-500">Sumber</dt>
                        <dd class="capitalize text-slate-400">{{ $jadwalEfektifHariIni->sumber }}</dd>
                    </div>
                </dl>
            @endif
        </x-card>

        <x-card title="Status Absensi Hari Ini">
            @if ($absensiHariIni === null)
                <x-empty-state title="Belum absen" description="Absensi hari ini belum tercatat." />
            @else
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Status</dt>
                        <dd class="font-medium capitalize text-slate-800">{{ $absensiHariIni->status }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Jam Masuk</dt>
                        <dd class="font-medium text-slate-800">{{ $absensiHariIni->jam_masuk?->format('H:i') ?? '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Jam Pulang</dt>
                        <dd class="font-medium text-slate-800">{{ $absensiHariIni->jam_pulang?->format('H:i') ?? '-' }}</dd>
                    </div>
                </dl>
            @endif
        </x-card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-card title="Tugas Produksi Hari Ini">
            @if ($produksiHariIni->isEmpty())
                <x-empty-state title="Belum ada produksi" description="Tidak ada jadwal produksi untuk hari ini." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="pb-2 pr-4 font-medium">Menu</th>
                                <th class="pb-2 pr-4 font-medium">Target</th>
                                <th class="pb-2 pr-4 font-medium">Hasil</th>
                                <th class="pb-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($produksiHariIni as $produksi)
                                <tr>
                                    <td class="py-2 pr-4 text-slate-700">{{ $produksi->menu?->nama ?? '-' }}</td>
                                    <td class="py-2 pr-4 text-slate-600">{{ number_format($produksi->target_porsi) }}</td>
                                    <td class="py-2 pr-4 text-slate-600">{{ number_format($produksi->hasil_porsi) }}</td>
                                    <td class="py-2 capitalize text-slate-600">{{ str_replace('_', ' ', $produksi->status) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card title="Distribusi Hari Ini">
            @if ($distribusiHariIni->isEmpty())
                <x-empty-state title="Belum ada distribusi" description="Tidak ada distribusi yang ditugaskan hari ini." />
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach ($distribusiHariIni as $distribusi)
                        <li class="flex items-center justify-between py-3 text-sm">
                            <div>
                                <p class="font-medium text-slate-700">{{ $distribusi->sekolah?->nama ?? '-' }}</p>
                                <p class="text-xs text-slate-500">{{ $distribusi->kode_distribusi }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-medium text-slate-700">{{ number_format($distribusi->jumlah_porsi) }} porsi</p>
                                <p class="text-xs capitalize text-slate-500">{{ str_replace('_', ' ', $distribusi->status) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>
@endsection
