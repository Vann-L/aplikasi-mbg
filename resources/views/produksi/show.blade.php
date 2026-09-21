@extends('layouts.app')

@section('title', 'Detail Produksi')
@section('subtitle', 'Kebutuhan porsi, bahan, dan progres produksi')

@php
    $kebutuhan = $produksi->kebutuhanPorsi();
    $selisih = $produksi->selisihPorsi();
    $canEdit = $canOperate && $produksi->status === \App\Models\Produksi::STATUS_BELUM_DIMULAI;
    $format = fn ($nilai) => rtrim(rtrim(number_format($nilai, 2, ',', '.'), '0'), ',');
    $statusLabels = [
        \App\Models\Produksi::STATUS_BELUM_DIMULAI => 'Belum Dimulai',
        \App\Models\Produksi::STATUS_PERSIAPAN => 'Persiapan',
        \App\Models\Produksi::STATUS_PENGOLAHAN => 'Pengolahan',
        \App\Models\Produksi::STATUS_QC => 'QC',
        \App\Models\Produksi::STATUS_PEMORSIAN => 'Pemorsian',
        \App\Models\Produksi::STATUS_PACKING => 'Packing',
        \App\Models\Produksi::STATUS_SELESAI => 'Selesai',
    ];
    $posisiStatus = array_search($produksi->status, \App\Models\Produksi::STATUS_FLOW, true);
@endphp

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

        <x-card title="Informasi Produksi">
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Menu</dt>
                    <dd class="font-medium text-slate-800">{{ $produksi->menu?->nama ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Tanggal</dt>
                    <dd class="text-slate-800">{{ $produksi->tanggal->format('d/m/Y') }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Kebutuhan Porsi</dt>
                    <dd class="font-medium text-slate-800">{{ number_format($kebutuhan) }} porsi</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Target Porsi</dt>
                    <dd class="font-medium text-slate-800">{{ number_format($produksi->target_porsi) }} porsi</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Hasil Produksi</dt>
                    <dd class="font-medium text-slate-800">{{ number_format($produksi->hasil_porsi) }} porsi</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Selisih</dt>
                    <dd class="font-medium {{ $selisih < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                        @if ($selisih < 0)
                            {{ number_format($selisih) }} (kekurangan)
                        @elseif ($selisih > 0)
                            +{{ number_format($selisih) }} (kelebihan)
                        @else
                            0
                        @endif
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    <dd>@include('produksi._status_badge', ['status' => $produksi->status])</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Dibuat oleh</dt>
                    <dd class="text-slate-800">{{ $produksi->createdBy?->name ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Dimulai</dt>
                    <dd class="text-slate-800">{{ $produksi->started_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Selesai</dt>
                    <dd class="text-slate-800">{{ $produksi->completed_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 sm:col-span-2">
                    <dt class="text-slate-500">Catatan</dt>
                    <dd class="text-slate-800">{{ $produksi->catatan ?: '-' }}</dd>
                </div>
            </dl>

            @if ($produksi->status !== \App\Models\Produksi::STATUS_BELUM_DIMULAI)
                <div class="mt-4 rounded-lg bg-slate-50 p-3">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Progres Status</p>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach (\App\Models\Produksi::STATUS_FLOW as $index => $item)
                            @php
                                $isPast = $index < $posisiStatus;
                                $isCurrent = $item === $produksi->status;
                                $chip = $isCurrent
                                    ? 'bg-emerald-600 text-white'
                                    : ($isPast ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500');
                            @endphp
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $chip }}">
                                {{ $statusLabels[$item] }}
                            </span>
                            @if (! $loop->last)
                                <span class="text-slate-300">→</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </x-card>

        <x-card title="Rencana Distribusi">
            @if ($canEdit)
                <form method="POST" action="{{ route($routePrefix . '.produksi.distribution.store', $produksi) }}"
                    class="mb-4 flex flex-col gap-2 rounded-lg bg-slate-50 p-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <label for="sekolah_id" class="block text-xs font-medium text-slate-600">Sekolah</label>
                        <select id="sekolah_id" name="sekolah_id"
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">Pilih sekolah</option>
                            @foreach ($sekolahs as $sekolah)
                                <option value="{{ $sekolah->id }}">{{ $sekolah->nama }}</option>
                            @endforeach
                        </select>
                        @error('sekolah_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="jumlah_porsi" class="block text-xs font-medium text-slate-600">Jumlah Porsi</label>
                        <input id="jumlah_porsi" name="jumlah_porsi" type="number" min="1"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('jumlah_porsi')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Tambah
                    </button>
                </form>
            @endif

            @if ($produksi->distribusis->isEmpty())
                <x-empty-state title="Belum ada rencana distribusi"
                    description="Tambahkan rencana distribusi untuk menentukan kebutuhan porsi produksi." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Sekolah</th>
                                <th class="px-3 py-2 font-medium">Jumlah Porsi</th>
                                @if ($canEdit)
                                    <th class="px-3 py-2 text-right font-medium">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($produksi->distribusis as $distribusi)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $distribusi->sekolah?->nama ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ number_format($distribusi->jumlah_porsi) }} porsi</td>
                                    @if ($canEdit)
                                        <td class="px-3 py-2">
                                            <div class="flex items-center justify-end gap-4">
                                                <details class="relative">
                                                    <summary class="cursor-pointer text-slate-600 hover:text-slate-900">Edit</summary>
                                                    <form method="POST"
                                                        action="{{ route($routePrefix . '.produksi.distribution.update', [$produksi, $distribusi]) }}"
                                                        class="absolute right-0 top-7 z-10 w-72 space-y-3 rounded-lg border border-slate-200 bg-white p-4 shadow-lg">
                                                        @csrf
                                                        @method('PUT')
                                                        <div>
                                                            <label class="block text-xs font-medium text-slate-600">Sekolah</label>
                                                            <select name="sekolah_id"
                                                                class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                                                @foreach ($sekolahs as $sekolah)
                                                                    <option value="{{ $sekolah->id }}" @selected($sekolah->id === $distribusi->sekolah_id)>
                                                                        {{ $sekolah->nama }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-slate-600">Jumlah Porsi</label>
                                                            <input type="number" name="jumlah_porsi" min="1" value="{{ $distribusi->jumlah_porsi }}"
                                                                class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                                        </div>
                                                        <div class="flex items-center justify-end gap-3">
                                                            <a href="#" onclick="this.closest('details').removeAttribute('open'); return false;"
                                                                class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
                                                            <button type="submit"
                                                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700">
                                                                Simpan
                                                            </button>
                                                        </div>
                                                    </form>
                                                </details>
                                                <form method="POST"
                                                    action="{{ route($routePrefix . '.produksi.distribution.destroy', [$produksi, $distribusi]) }}"
                                                    onsubmit="return confirm('Hapus rencana distribusi ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-700">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-sm">
                    <span class="text-slate-500">Total kebutuhan porsi (target produksi)</span>
                    <span class="font-semibold text-slate-800">{{ number_format($kebutuhan) }} porsi</span>
                </div>
            @endif
        </x-card>

        <x-card title="Bahan Produksi">
            @if ($canEdit)
                <form method="POST" action="{{ route($routePrefix . '.produksi.ingredient.store', $produksi) }}"
                    class="mb-4 flex flex-col gap-2 rounded-lg bg-slate-50 p-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <label for="bahan_baku_id" class="block text-xs font-medium text-slate-600">Bahan Baku</label>
                        <select id="bahan_baku_id" name="bahan_baku_id"
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">Pilih bahan</option>
                            @foreach ($bahanBakus as $bahan)
                                <option value="{{ $bahan->id }}">{{ $bahan->nama }} ({{ $bahan->satuan }})</option>
                            @endforeach
                        </select>
                        @error('bahan_baku_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="jumlah" class="block text-xs font-medium text-slate-600">Jumlah</label>
                        <input id="jumlah" name="jumlah" type="number" step="0.01" min="0.01"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('jumlah')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Tambah
                    </button>
                </form>
            @endif

            @if ($produksi->produksiBahans->isEmpty())
                <x-empty-state title="Belum ada bahan produksi"
                    description="Tambahkan bahan yang digunakan agar stoknya dapat divalidasi saat produksi dimulai." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Bahan</th>
                                <th class="px-3 py-2 font-medium">Jumlah</th>
                                <th class="px-3 py-2 font-medium">Stok Tersedia</th>
                                <th class="px-3 py-2 font-medium">Status Stok</th>
                                @if ($canEdit)
                                    <th class="px-3 py-2 text-right font-medium">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($produksi->produksiBahans as $produksiBahan)
                                @php
                                    $bahan = $produksiBahan->bahanBaku;
                                    $stok = $bahan ? ($stokTersedia->get($bahan->id) ?? 0.0) : 0.0;
                                    $stokStatus = $bahan ? $bahan->statusStok($stok) : null;
                                    $stokBadge = [
                                        \App\Models\BahanBaku::STOK_STATUS_HABIS => 'bg-red-50 text-red-700',
                                        \App\Models\BahanBaku::STOK_STATUS_RENDAH => 'bg-amber-50 text-amber-700',
                                        \App\Models\BahanBaku::STOK_STATUS_NORMAL => 'bg-emerald-50 text-emerald-700',
                                    ];
                                    $stokLabel = [
                                        \App\Models\BahanBaku::STOK_STATUS_HABIS => 'Habis',
                                        \App\Models\BahanBaku::STOK_STATUS_RENDAH => 'Rendah',
                                        \App\Models\BahanBaku::STOK_STATUS_NORMAL => 'Normal',
                                    ];
                                @endphp
                                <tr>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $bahan?->nama ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">
                                        {{ $format($produksiBahan->jumlah) }} {{ $produksiBahan->satuan }}
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ $format($stok) }} {{ $bahan?->satuan }}</td>
                                    <td class="px-3 py-2">
                                        @if ($stokStatus)
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $stokBadge[$stokStatus] }}">
                                                {{ $stokLabel[$stokStatus] }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    @if ($canEdit)
                                        <td class="px-3 py-2">
                                            <div class="flex items-center justify-end">
                                                <form method="POST"
                                                    action="{{ route($routePrefix . '.produksi.ingredient.destroy', [$produksi, $produksiBahan]) }}"
                                                    onsubmit="return confirm('Hapus bahan produksi ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-700">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>

        <x-card title="Aksi Produksi">
            @if ($produksi->status === \App\Models\Produksi::STATUS_BELUM_DIMULAI)
                @if ($canOperate)
                    <p class="mb-3 text-sm text-slate-600">
                        Memulai produksi akan memotong stok seluruh bahan produksi secara bersamaan.
                    </p>
                    <form method="POST" action="{{ route($routePrefix . '.produksi.start', $produksi) }}">
                        @csrf
                        <button type="submit"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Mulai Produksi
                        </button>
                    </form>
                @else
                    <p class="text-sm text-slate-500">Produksi belum dimulai.</p>
                @endif
            @elseif ($produksi->status !== \App\Models\Produksi::STATUS_SELESAI)
                @if ($canOperate)
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <form method="POST" action="{{ route($routePrefix . '.produksi.status.update', $produksi) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="status" value="{{ $produksi->nextStatus() }}">
                                <p class="mb-2 text-sm text-slate-600">
                                    Status saat ini: {{ $statusLabels[$produksi->status] }}.
                                    Lanjutkan ke tahap berikutnya secara berurutan.
                                </p>
                                <button type="submit"
                                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                    Lanjut ke {{ $statusLabels[$produksi->nextStatus()] }}
                                </button>
                            </form>
                        </div>
                        <form method="POST" action="{{ route($routePrefix . '.produksi.result.update', $produksi) }}"
                            class="rounded-lg bg-slate-50 p-3">
                            @csrf
                            @method('PUT')
                            <label for="hasil_porsi" class="block text-xs font-medium text-slate-600">Hasil Produksi (porsi)</label>
                            <div class="mt-1 flex items-center gap-2">
                                <input id="hasil_porsi" name="hasil_porsi" type="number" min="0"
                                    value="{{ $produksi->hasil_porsi ?: '' }}"
                                    class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                <button type="submit"
                                    class="rounded-lg border border-emerald-600 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                                    Catat Hasil
                                </button>
                            </div>
                            @error('hasil_porsi')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </form>
                    </div>
                @else
                    <p class="text-sm text-slate-500">Status: {{ $statusLabels[$produksi->status] }}.</p>
                @endif
            @else
                <p class="text-sm text-slate-600">
                    Produksi selesai pada {{ $produksi->completed_at?->format('d/m/Y H:i') }}.
                </p>
            @endif
        </x-card>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a href="{{ route($routePrefix . '.produksi.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                Kembali ke Produksi
            </a>

            @if ($routePrefix === 'admin' && $produksi->status === \App\Models\Produksi::STATUS_BELUM_DIMULAI
                && $produksi->distribusis->isEmpty() && $produksi->produksiBahans->isEmpty())
                <form method="POST" action="{{ route('admin.produksi.destroy', $produksi) }}"
                    onsubmit="return confirm('Hapus produksi ini?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-700">Hapus Produksi</button>
                </form>
            @endif
        </div>
    </div>
@endsection