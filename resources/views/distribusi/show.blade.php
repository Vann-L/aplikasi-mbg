@extends('layouts.app')

@section('title', 'Detail Distribusi')
@section('subtitle', 'Penjadwalan dan status pengiriman')

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

        @if (! $kebutuhanTerpenuhi && in_array($distribusi->status, \App\Models\Distribusi::STATUS_PERENCANAAN, true))
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                Produksi belum memenuhi kebutuhan porsi. Pengiriman tidak dapat dilakukan sampai hasil produksi
                mencukupi kebutuhan.
            </div>
        @endif

        <x-card title="Informasi Distribusi">
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Kode Distribusi</dt>
                    <dd class="font-mono font-medium text-slate-800">{{ $distribusi->kode_distribusi }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    <dd>@include('distribusi._status_badge', ['status' => $distribusi->status])</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Tanggal</dt>
                    <dd class="text-slate-800">{{ $distribusi->tanggal->format('d/m/Y') }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Sekolah</dt>
                    <dd class="font-medium text-slate-800">{{ $distribusi->sekolah?->nama ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Menu / Produksi</dt>
                    <dd class="font-medium text-slate-800">{{ $distribusi->produksi?->menu?->nama ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Jumlah Porsi</dt>
                    <dd class="font-medium text-slate-800">{{ number_format($distribusi->jumlah_porsi) }} porsi</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Petugas</dt>
                    <dd class="text-slate-800">{{ $distribusi->petugas?->nama ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Kendaraan</dt>
                    <dd class="text-slate-800">{{ $distribusi->kendaraan ?: '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Jam Berangkat</dt>
                    <dd class="text-slate-800">{{ $distribusi->jam_berangkat?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Dibuat oleh</dt>
                    <dd class="text-slate-800">{{ $distribusi->createdBy?->name ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 sm:col-span-2">
                    <dt class="text-slate-500">Catatan</dt>
                    <dd class="text-slate-800">{{ $distribusi->catatan ?: '-' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Informasi Produksi">
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Target Produksi</dt>
                    <dd class="font-medium text-slate-800">{{ number_format($distribusi->produksi->target_porsi) }} porsi</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Hasil Produksi</dt>
                    <dd class="font-medium text-slate-800">{{ number_format($distribusi->produksi->hasil_porsi) }} porsi</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Kebutuhan Pemenuhan</dt>
                    <dd class="font-medium {{ $kebutuhanTerpenuhi ? 'text-emerald-600' : 'text-red-600' }}">
                        {{ $kebutuhanTerpenuhi ? 'Tercukupi' : 'Belum tercukupi' }}
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Status Pengiriman</dt>
                    <dd>@include('distribusi._status_badge', ['status' => $distribusi->status])</dd>
                </div>
            </dl>
        </x-card>

        @if ($canAct)
            <x-card title="Aksi Distribusi">
                @if ($distribusi->status === \App\Models\Distribusi::STATUS_DIJADWALKAN)
                    <form method="POST" action="{{ route($routePrefix . '.distribusi.siapkan', $distribusi) }}">
                        @csrf
                        <p class="mb-3 text-sm text-slate-600">
                            Siapkan distribusi untuk memulai proses pengiriman. Pastikan petugas dan kendaraan
                            sudah ditentukan.
                        </p>
                        <button type="submit"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Siapkan Distribusi
                        </button>
                    </form>
                @elseif ($distribusi->status === \App\Models\Distribusi::STATUS_DISIAPKAN)
                    <form method="POST" action="{{ route($routePrefix . '.distribusi.kirim', $distribusi) }}">
                        @csrf
                        <p class="mb-3 text-sm text-slate-600">
                            Kirim distribusi setelah produksi memenuhi kebutuhan dan petugas sudah ditentukan.
                        </p>
                        <button type="submit"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Kirim Distribusi
                        </button>
                    </form>
                @elseif ($distribusi->status === \App\Models\Distribusi::STATUS_DIKIRIM)
                    <p class="text-sm text-slate-600">
                        Distribusi sudah dikirim pada {{ $distribusi->jam_berangkat?->format('d/m/Y H:i') }}.
                        Bukti penerimaan akan dicatat pada fase penerimaan.
                    </p>
                @else
                    <p class="text-sm text-slate-600">Distribusi selesai diproses.</p>
                @endif
            </x-card>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route($routePrefix . '.distribusi.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                Kembali ke Distribusi
            </a>

            @if ($routePrefix === 'admin' && in_array($distribusi->status, \App\Models\Distribusi::STATUS_PERENCANAAN, true))
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.distribusi.edit', $distribusi) }}"
                        class="text-sm text-slate-600 hover:text-slate-900">Jadwalkan Ulang</a>
                    <form method="POST" action="{{ route('admin.distribusi.destroy', $distribusi) }}"
                        onsubmit="return confirm('Hapus distribusi ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm text-red-600 hover:text-red-700">Hapus Distribusi</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection