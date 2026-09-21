@extends('layouts.app')

@section('title', 'Laporan Produksi')
@section('subtitle', 'Ringkasan hasil produksi terhadap target porsi')

@section('content')
    <div class="space-y-4">
        <div class="no-print">
            @include('laporan._nav', ['routePrefix' => $routePrefix, 'active' => $active])
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route($routePrefix . '.laporan.produksi') }}"
                class="no-print flex flex-col gap-2 sm:flex-row">
                <input type="date" name="tanggal_awal" value="{{ $tanggalAwal }}"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <input type="date" name="tanggal_akhir" value="{{ $tanggalAkhir }}"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <select name="status"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\Produksi::STATUS_FLOW as $item)
                        <option value="{{ $item }}" @selected($status === $item)>{{ ucwords(str_replace('_', ' ', $item)) }}</option>
                    @endforeach
                </select>
                <button type="submit"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Cari
                </button>
                <a href="{{ route($routePrefix . '.laporan.produksi') }}"
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
            <h1 class="text-lg font-bold text-slate-800">Laporan Produksi</h1>
            <p class="text-xs text-slate-500">
                Periode: {{ $tanggalAwal ?: 'Semua' }} &ndash; {{ $tanggalAkhir ?: 'Semua' }}
                &middot; Status: {{ $status ?: 'Semua' }}
            </p>
        </div>

        <x-card>
            @if ($produksi->isEmpty())
                <x-empty-state title="Belum ada data produksi"
                    description="Tidak ada data produksi yang cocok dengan filter yang dipilih." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Tanggal</th>
                                <th class="px-3 py-2 font-medium">Menu</th>
                                <th class="px-3 py-2 font-medium">Target</th>
                                <th class="px-3 py-2 font-medium">Hasil</th>
                                <th class="px-3 py-2 font-medium">Selisih</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($produksi as $item)
                                @php
                                    $selisih = (int) $item->selisih;
                                    $selisihLabel = $selisih > 0 ? '+'.number_format($selisih) : number_format($selisih);
                                    $selisihBadge = $selisih < 0 ? 'text-red-600' : 'text-emerald-600';
                                    $selisihText = $selisih < 0 ? 'kekurangan' : ($selisih > 0 ? 'kelebihan' : 'sesuai');
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->tanggal->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $item->menu?->nama ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ number_format($item->target_porsi) }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ number_format($item->hasil_porsi) }}</td>
                                    <td class="px-3 py-2 font-medium {{ $selisihBadge }}">
                                        {{ $selisihLabel }}
                                        <span class="font-normal text-slate-500">{{ $selisihText }}</span>
                                    </td>
                                    <td class="px-3 py-2">@include('produksi._status_badge', ['status' => $item->status])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $produksi->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection