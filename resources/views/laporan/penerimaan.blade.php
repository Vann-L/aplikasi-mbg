@extends('layouts.app')

@section('title', 'Laporan Penerimaan')
@section('subtitle', 'Ringkasan penerimaan MBG di sekolah')

@section('content')
    <div class="space-y-4">
        <div class="no-print">
            @include('laporan._nav', ['routePrefix' => $routePrefix, 'active' => $active])
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route($routePrefix . '.laporan.penerimaan') }}"
                class="no-print flex flex-col gap-2 sm:flex-row">
                <input type="date" name="tanggal_awal" value="{{ $tanggalAwal }}"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <input type="date" name="tanggal_akhir" value="{{ $tanggalAkhir }}"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <select name="sekolah"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Semua sekolah</option>
                    @foreach ($sekolahList as $item)
                        <option value="{{ $item->id }}" @selected($sekolahId === $item->id)>{{ $item->nama }}</option>
                    @endforeach
                </select>
                <button type="submit"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Cari
                </button>
                <a href="{{ route($routePrefix . '.laporan.penerimaan') }}"
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
            <h1 class="text-lg font-bold text-slate-800">Laporan Penerimaan</h1>
            <p class="text-xs text-slate-500">
                Periode: {{ $tanggalAwal ?: 'Semua' }} &ndash; {{ $tanggalAkhir ?: 'Semua' }}
                &middot; Sekolah: {{ $sekolahList->firstWhere('id', $sekolahId)?->nama ?? 'Semua' }}
            </p>
        </div>

        <x-card>
            @if ($distribusi->isEmpty())
                <x-empty-state title="Belum ada data penerimaan"
                    description="Belum ada distribusi dengan catatan penerimaan yang cocok dengan filter yang dipilih." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Kode</th>
                                <th class="px-3 py-2 font-medium">Tanggal</th>
                                <th class="px-3 py-2 font-medium">Sekolah</th>
                                <th class="px-3 py-2 font-medium">Dikirim</th>
                                <th class="px-3 py-2 font-medium">Diterima</th>
                                <th class="px-3 py-2 font-medium">Selisih</th>
                                <th class="px-3 py-2 font-medium">Penerima</th>
                                <th class="px-3 py-2 font-medium">Waktu Diterima</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($distribusi as $item)
                                @php
                                    $selisih = $item->selisihTerkirimDiterima();
                                    $selisihText = $selisih === null ? '-' : ($selisih > 0 ? '+'.number_format($selisih) : number_format($selisih));
                                    $selisihBadge = $selisih > 0 ? 'text-red-600' : 'text-emerald-600';
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $item->kode_distribusi }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->tanggal->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $item->sekolah?->nama ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ number_format($item->jumlah_porsi) }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ number_format($item->penerimaan?->jumlah_diterima ?? 0) }}</td>
                                    <td class="px-3 py-2 font-medium {{ $selisih === null ? 'text-slate-400' : $selisihBadge }}">{{ $selisihText }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->penerimaan?->penerima_nama ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">
                                        {{ $item->penerimaan?->waktu_diterima?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2">@include('distribusi._status_badge', ['status' => $item->status])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $distribusi->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection