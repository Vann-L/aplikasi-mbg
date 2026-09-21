@extends('layouts.app')

@section('title', 'Distribusi')
@section('subtitle', 'Pengiriman MBG ke sekolah berdasarkan rencana distribusi')

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
            <form method="GET" action="{{ route($routePrefix . '.distribusi.index') }}"
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
                    Cari
                </button>
            </form>
        </div>

        <x-card>
            @if ($distribusi->isEmpty())
                <x-empty-state title="Belum ada distribusi"
                    description="Rencana distribusi dibuat melalui detail produksi, lalu dijalankan pada halaman ini." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Kode</th>
                                <th class="px-3 py-2 font-medium">Tanggal</th>
                                <th class="px-3 py-2 font-medium">Sekolah</th>
                                <th class="px-3 py-2 font-medium">Menu</th>
                                <th class="px-3 py-2 font-medium">Porsi</th>
                                <th class="px-3 py-2 font-medium">Petugas</th>
                                <th class="px-3 py-2 font-medium">Kendaraan</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                                <th class="px-3 py-2 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($distribusi as $item)
                                <tr>
                                    <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $item->kode_distribusi }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->tanggal->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $item->sekolah?->nama ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->produksi?->menu?->nama ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ number_format($item->jumlah_porsi) }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->petugas?->nama ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->kendaraan ?: '-' }}</td>
                                    <td class="px-3 py-2">@include('distribusi._status_badge', ['status' => $item->status])</td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center justify-end">
                                            <a href="{{ route($routePrefix . '.distribusi.show', $item) }}"
                                                class="text-slate-600 hover:text-slate-900">Detail</a>
                                        </div>
                                    </td>
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