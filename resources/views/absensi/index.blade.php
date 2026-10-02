@extends('layouts.app')

@section('title', 'Absensi Pegawai')
@section('subtitle', 'Rekap absensi pegawai SPPG')

@section('content')
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route($routePrefix . '.absensi.index') }}"
                class="flex flex-col gap-2 sm:flex-row">
                <input type="date" name="tanggal" value="{{ $tanggal }}"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <select name="status"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Semua status</option>
                    @foreach ($availableStatuses as $item)
                        <option value="{{ $item }}" @selected($status === $item)>{{ ucwords(str_replace('_', ' ', $item)) }}</option>
                    @endforeach
                </select>
                <button type="submit"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Tampilkan
                </button>
                <a href="{{ route($routePrefix . '.absensi.qr') }}"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-emerald-700">
                    Buka Display QR
                </a>
            </form>
        </div>

        <x-card>
            @if ($rows->isEmpty())
                <x-empty-state title="Belum ada pegawai aktif"
                    description="Tidak ada pegawai aktif yang dapat direkap pada tanggal ini." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Pegawai</th>
                                <th class="px-3 py-2 font-medium">Jadwal</th>
                                <th class="px-3 py-2 font-medium">Jam Masuk</th>
                                <th class="px-3 py-2 font-medium">Jam Pulang</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($rows as $baris)
                                @php
                                    $efektif = $baris['efektif'];
                                    $absensi = $baris['absensi'];
                                @endphp
                                <tr>
                                    <td class="px-3 py-2">
                                        <p class="font-medium text-slate-700">{{ $baris['pegawai']->nama }}</p>
                                        <p class="text-xs text-slate-500">{{ $baris['pegawai']->jabatan ?? '-' }} &middot; {{ $baris['pegawai']->bagian ?? '-' }}</p>
                                    </td>
                                    <td class="px-3 py-2">
                                        <p class="text-slate-600">
                                            @if ($efektif !== null && $efektif->status === \App\Models\JadwalPegawai::STATUS_TERJADWAL)
                                                {{ $efektif->jamMasuk?->format('H:i') ?? '-' }} &ndash; {{ $efektif->jamPulang?->format('H:i') ?? '-' }}
                                            @elseif ($efektif !== null)
                                                {{ $efektif->status === \App\Models\JadwalPegawai::STATUS_LIBUR ? 'Libur' : 'Izin' }}
                                            @else
                                                -
                                            @endif
                                        </p>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ $absensi?->jam_masuk?->format('H:i') ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $absensi?->jam_pulang?->format('H:i') ?? '-' }}</td>
                                    <td class="px-3 py-2">@include('absensi._status_badge', ['status' => $baris['status']])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $rows->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection