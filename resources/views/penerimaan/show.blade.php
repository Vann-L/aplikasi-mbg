@extends('layouts.app')

@section('title', 'Penerimaan '. $distribusi->kode_distribusi)
@section('subtitle', 'Detail pengiriman dan penerimaan di sekolah')

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
                    <dt class="text-slate-500">Jam Berangkat</dt>
                    <dd class="text-slate-800">{{ $distribusi->jam_berangkat?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Informasi Sekolah & Pengiriman">
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Sekolah</dt>
                    <dd class="font-medium text-slate-800">{{ $distribusi->sekolah?->nama ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Menu / Produksi</dt>
                    <dd class="font-medium text-slate-800">{{ $distribusi->produksi?->menu?->nama ?? '-' }}</dd>
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
                    <dt class="text-slate-500">Dibuat oleh</dt>
                    <dd class="text-slate-800">{{ $distribusi->createdBy?->name ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 sm:col-span-2">
                    <dt class="text-slate-500">Catatan Distribusi</dt>
                    <dd class="text-slate-800">{{ $distribusi->catatan ?: '-' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Ringkasan Penerimaan">
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Jumlah Dikirim</dt>
                    <dd class="font-semibold text-slate-800">{{ number_format($distribusi->jumlah_porsi) }} porsi</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Jumlah Diterima</dt>
                    <dd class="font-semibold text-slate-800">
                        {{ $distribusi->penerimaan ? number_format($distribusi->penerimaan->jumlah_diterima).' porsi' : '-' }}
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Selisih</dt>
                    <dd class="font-semibold {{ $distribusi->selisihTerkirimDiterima() === 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                        @if ($distribusi->selisihTerkirimDiterima() !== null)
                            {{ number_format($distribusi->selisihTerkirimDiterima()) }} porsi
                        @else
                            -
                        @endif
                    </dd>
                </div>
            </dl>
            @if ($distribusi->selisihTerkirimDiterima() !== null && $distribusi->selisihTerkirimDiterima() > 0)
                <p class="mt-3 text-sm text-amber-700">
                    Jumlah diterima lebih sedikit dari yang dikirim. Gunakan catatan penerimaan untuk menjelaskan selisihnya.
                </p>
            @endif
        </x-card>

        @if ($distribusi->penerimaan)
            <x-card title="Penerimaan di Sekolah">
                <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">Jumlah Diterima</dt>
                        <dd class="font-semibold text-slate-800">{{ number_format($distribusi->penerimaan->jumlah_diterima) }} porsi</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">Waktu Diterima</dt>
                        <dd class="text-slate-800">{{ $distribusi->penerimaan->waktu_diterima->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">Nama Penerima</dt>
                        <dd class="font-medium text-slate-800">{{ $distribusi->penerimaan->penerima_nama }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-slate-500">Dicatat oleh</dt>
                        <dd class="text-slate-800">{{ $distribusi->penerimaan->createdBy?->name ?? '-' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4 sm:col-span-2">
                        <dt class="text-slate-500">Catatan Penerimaan</dt>
                        <dd class="text-slate-800">{{ $distribusi->penerimaan->catatan ?: '-' }}</dd>
                    </div>
                </dl>

                @if ($distribusi->penerimaan->foto_bukti)
                    <div class="mt-4">
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Foto Bukti</p>
                        <img src="{{ asset('storage/'.$distribusi->penerimaan->foto_bukti) }}" alt="Foto bukti penerimaan"
                            class="max-h-64 rounded-lg border border-slate-200 object-contain">
                    </div>
                @endif
            </x-card>
        @endif

        @if ($canAct)
            <x-card title="Aksi Penerimaan">
                @if ($distribusi->status === \App\Models\Distribusi::STATUS_DIKIRIM)
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-600">
                            Distribusi sudah dikirim. Catat penerimaan di sekolah untuk mengubah status menjadi diterima.
                        </p>
                        <a href="{{ route($routePrefix . '.penerimaan.create', $distribusi) }}"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Catat Penerimaan
                        </a>
                    </div>
                @elseif ($distribusi->status === \App\Models\Distribusi::STATUS_DITERIMA)
                    <form method="POST" action="{{ route($routePrefix . '.penerimaan.selesai', $distribusi) }}">
                        @csrf
                        <p class="mb-3 text-sm text-slate-600">
                            Distribusi sudah diterima sekolah. Finalisasi untuk menandai distribusi selesai.
                        </p>
                        <button type="submit"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Selesaikan Distribusi
                        </button>
                    </form>
                @else
                    <p class="text-sm text-slate-600">Distribusi telah selesai.</p>
                @endif
            </x-card>
        @endif

        <a href="{{ route($routePrefix . '.penerimaan.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
            Kembali ke Penerimaan
        </a>
    </div>
@endsection