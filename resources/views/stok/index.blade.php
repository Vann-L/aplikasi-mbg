@extends('layouts.app')

@section('title', 'Stok Bahan Baku')
@section('subtitle', 'Ringkasan ketersediaan stok bahan baku')

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

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                Stok tersedia dihitung dari mutasi masuk, keluar, dan penyesuaian.
            </p>

            <div class="flex items-center gap-3">
                @if ($canCreate)
                    <a href="{{ route($routePrefix . '.stok.create') }}"
                        class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Tambah Mutasi
                    </a>
                @endif

                <a href="{{ route($routePrefix . '.stok.history') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Riwayat Mutasi
                </a>
            </div>
        </div>

        <x-card>
            @if ($bahanBaku->isEmpty())
                <x-empty-state title="Belum ada bahan baku"
                    description="Data bahan baku belum tersedia. Tambahkan bahan baku terlebih dahulu." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Nama Bahan</th>
                                <th class="px-3 py-2 font-medium">Satuan</th>
                                <th class="px-3 py-2 font-medium">Stok Tersedia</th>
                                <th class="px-3 py-2 font-medium">Stok Minimum</th>
                                <th class="px-3 py-2 font-medium">Status Stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($bahanBaku as $item)
                                @php
                                    $tersedia = $stokTersedia->get($item->id) ?? 0.0;
                                    $status = $item->statusStok($tersedia);
                                    $format = fn ($nilai) => rtrim(rtrim(number_format($nilai, 2, ',', '.'), '0'), ',');
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $item->nama }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->satuan }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $format($tersedia) }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $format((float) $item->stok_minimum) }}</td>
                                    <td class="px-3 py-2">
                                        @php
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
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge[$status] }}">
                                            {{ $label[$status] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $bahanBaku->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection