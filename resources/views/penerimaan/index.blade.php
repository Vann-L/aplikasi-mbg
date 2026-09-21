@extends('layouts.app')

@section('title', 'Penerimaan')
@section('subtitle', 'Pencatatan penerimaan MBG di sekolah')

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
            <form method="GET" action="{{ route($routePrefix . '.penerimaan.index') }}"
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
                <x-empty-state title="Belum ada distribusi untuk penerimaan"
                    description="Distribusi yang sudah dikirim akan tampil di sini untuk dicatat penerimaannya." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Kode</th>
                                <th class="px-3 py-2 font-medium">Tanggal</th>
                                <th class="px-3 py-2 font-medium">Sekolah</th>
                                <th class="px-3 py-2 font-medium">Menu</th>
                                <th class="px-3 py-2 font-medium">Dikirim</th>
                                <th class="px-3 py-2 font-medium">Petugas</th>
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
                                    <td class="px-3 py-2">@include('distribusi._status_badge', ['status' => $item->status])</td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center justify-end">
                                            @if ($item->status === \App\Models\Distribusi::STATUS_DIKIRIM && $routePrefix !== 'kepala')
                                                <a href="{{ route($routePrefix . '.penerimaan.create', $item) }}"
                                                    class="font-medium text-emerald-600 hover:text-emerald-700">Catat</a>
                                            @else
                                                <a href="{{ route($routePrefix . '.penerimaan.show', $item) }}"
                                                    class="text-slate-600 hover:text-slate-900">Detail</a>
                                            @endif
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