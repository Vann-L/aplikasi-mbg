@extends('layouts.app')

@section('title', 'Riwayat Mutasi Stok')
@section('subtitle', 'Riwayat mutasi masuk, keluar, dan penyesuaian stok')

@section('content')
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                Seluruh mutasi stok bahan baku yang pernah dicatat.
            </p>

            <a href="{{ route($routePrefix . '.stok.index') }}"
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                Kembali ke Stok
            </a>
        </div>

        <x-card>
            @if ($mutasi->isEmpty())
                <x-empty-state title="Belum ada mutasi stok"
                    description="Belum ada mutasi stok yang dicatat." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Tanggal</th>
                                <th class="px-3 py-2 font-medium">Bahan Baku</th>
                                <th class="px-3 py-2 font-medium">Tipe</th>
                                <th class="px-3 py-2 font-medium">Jumlah</th>
                                <th class="px-3 py-2 font-medium">Keterangan</th>
                                <th class="px-3 py-2 font-medium">Dicatat oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($mutasi as $item)
                                @php
                                    $badge = [
                                        \App\Models\StokMutation::TIPE_MASUK => 'bg-emerald-50 text-emerald-700',
                                        \App\Models\StokMutation::TIPE_KELUAR => 'bg-red-50 text-red-700',
                                        \App\Models\StokMutation::TIPE_PENYESUAIAN => 'bg-amber-50 text-amber-700',
                                    ];
                                    $label = [
                                        \App\Models\StokMutation::TIPE_MASUK => 'Masuk',
                                        \App\Models\StokMutation::TIPE_KELUAR => 'Keluar',
                                        \App\Models\StokMutation::TIPE_PENYESUAIAN => 'Penyesuaian',
                                    ];
                                    $format = fn ($nilai) => rtrim(rtrim(number_format($nilai, 2, ',', '.'), '0'), ',');
                                    $jumlah = $item->tipe === \App\Models\StokMutation::TIPE_KELUAR
                                        ? '- ' . $format($item->jumlah)
                                        : ($item->tipe === \App\Models\StokMutation::TIPE_MASUK
                                            ? '+ ' . $format($item->jumlah)
                                            : $format($item->jumlah));
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->tanggal->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $item->bahanBaku?->nama ?? '-' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge[$item->tipe] }}">
                                            {{ $label[$item->tipe] }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $jumlah }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->keterangan ?: '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->user?->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $mutasi->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection