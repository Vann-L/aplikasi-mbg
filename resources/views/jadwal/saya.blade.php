@extends('layouts.app')

@section('title', 'Jadwal Saya')
@section('subtitle', 'Jadwal kerja efektif Anda')

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

        @if ($pegawai === null)
            <x-card>
                <x-empty-state title="Akun belum terhubung ke pegawai"
                    description="Hubungkan akun Anda dengan data pegawai agar jadwal kerja dapat ditampilkan." />
            </x-card>
        @else
            <x-card title="Jadwal Kerja {{ $pegawai->nama }}">
                <p class="mb-3 text-xs text-slate-500">
                    Jadwal efektif dari hari kemarin sampai empat minggu ke depan. Jadwal dapat berubah oleh override
                    tanggal, kalender operasional, atau pola mingguan.
                </p>

                @if ($jadwal->isEmpty())
                    <x-empty-state title="Belum ada jadwal" description="Jadwal kerja untuk Anda akan tampil di sini." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                    <th class="px-3 py-2 font-medium">Tanggal</th>
                                    <th class="px-3 py-2 font-medium">Jam Kerja</th>
                                    <th class="px-3 py-2 font-medium">Status</th>
                                    <th class="px-3 py-2 font-medium">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($jadwal as $baris)
                                    @php
                                        $efektif = $baris['efektif'];
                                        $statusLabel = $efektif?->status ?? 'tidak_terjadwal';
                                        $jam = $efektif !== null
                                            ? ($efektif->jamMasuk?->format('H:i').' - '.$efektif->jamPulang?->format('H:i'))
                                            : null;
                                        $keterangan = $baris['catatan']
                                            ?? ($baris['kalender']?->alasan ?: null);
                                    @endphp
                                    <tr>
                                        <td class="px-3 py-2 text-slate-600">
                                            {{ $baris['tanggal']->format('d/m/Y') }}
                                            <span class="text-xs text-slate-400">({{ $baris['tanggal']->isoFormat('ddd') }})</span>
                                        </td>
                                        <td class="px-3 py-2 text-slate-600">{{ $jam ?: '-' }}</td>
                                        <td class="px-3 py-2">
                                            @if ($efektif === null)
                                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Tanpa Jadwal</span>
                                            @else
                                                @include('jadwal._status_badge', ['status' => $statusLabel])
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-slate-600">{{ $keterangan ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-card>
        @endif
    </div>
@endsection