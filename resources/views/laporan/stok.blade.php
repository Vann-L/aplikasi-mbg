@extends('layouts.app')

@section('title', 'Laporan Stok')
@section('subtitle', 'Kondisi stok bahan baku saat ini')

@section('content')
    <div class="space-y-4">
        <div class="no-print">
            @include('laporan._nav', ['routePrefix' => $routePrefix, 'active' => $active])
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route($routePrefix . '.laporan.stok') }}"
                class="no-print flex flex-col gap-2 sm:flex-row">
                <select name="bahan_baku"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Semua bahan</option>
                    @foreach ($bahanBakuList as $item)
                        <option value="{{ $item->id }}" @selected($bahanBakuId === $item->id)>{{ $item->nama }}</option>
                    @endforeach
                </select>
                <select name="status"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Semua status</option>
                    @foreach ([
                        \App\Models\BahanBaku::STOK_STATUS_HABIS => 'Habis',
                        \App\Models\BahanBaku::STOK_STATUS_RENDAH => 'Rendah',
                        \App\Models\BahanBaku::STOK_STATUS_NORMAL => 'Normal',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Cari
                </button>
                <a href="{{ route($routePrefix . '.laporan.stok') }}"
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
            <h1 class="text-lg font-bold text-slate-800">Laporan Stok</h1>
            <p class="text-xs text-slate-500">
                Bahan: {{ $bahanBakuList->firstWhere('id', $bahanBakuId)?->nama ?? 'Semua' }}
                &middot; Status: {{ $status ?: 'Semua' }}
            </p>
        </div>

        <x-card>
            @if ($rows->isEmpty())
                <x-empty-state title="Belum ada data stok"
                    description="Tidak ada bahan baku yang cocok dengan filter yang dipilih." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Bahan</th>
                                <th class="px-3 py-2 font-medium">Satuan</th>
                                <th class="px-3 py-2 font-medium">Stok Tersedia</th>
                                <th class="px-3 py-2 font-medium">Stok Minimum</th>
                                <th class="px-3 py-2 font-medium">Status Stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($rows as $row)
                                @php
                                    $item = $row['bahan'];
                                    $format = fn ($nilai) => rtrim(rtrim(number_format($nilai, 2, ',', '.'), '0'), ',');
                                    $badge = [
                                        \App\Models\BahanBaku::STOK_STATUS_HABIS => 'bg-red-50 text-red-700',
                                        \App\Models\BahanBaku::STOK_STATUS_RENDAH => 'bg-amber-50 text-amber-700',
                                        \App\Models\BahanBaku::STOK_STATUS_NORMAL => 'bg-emerald-50 text-emerald-700',
                                    ];
                                    $label = [
                                        \App\Models\BahanBaku::STOK_STATUS_HABIS => 'Habis',
                                        \App\Models\BahanBaku::STOK_STATUS_RENDAH => 'Rendah',
                                        \App\Models\BahanBaku::STOK_STATUS_NORMAL => 'Normal',
                                    ];
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $item->nama }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->satuan }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $format($row['tersedia']) }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $format((float) $item->stok_minimum) }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge[$row['status']] }}">
                                            {{ $label[$row['status']] }}
                                        </span>
                                    </td>
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