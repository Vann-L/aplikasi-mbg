@extends('layouts.app')

@section('title', 'Catat Penerimaan')
@section('subtitle', $distribusi->kode_distribusi.' — '.($distribusi->sekolah?->nama ?? 'Sekolah'))

@section('content')
    <div class="space-y-4">
        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <x-card title="Ringkasan Pengiriman">
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Kode Distribusi</dt>
                    <dd class="font-mono font-medium text-slate-800">{{ $distribusi->kode_distribusi }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Menu</dt>
                    <dd class="font-medium text-slate-800">{{ $distribusi->produksi?->menu?->nama ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Petugas</dt>
                    <dd class="text-slate-800">{{ $distribusi->petugas?->nama ?? '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 sm:col-span-3">
                    <dt class="text-slate-500">Jumlah Dikirim</dt>
                    <dd class="font-semibold text-slate-800">{{ number_format($distribusi->jumlah_porsi) }} porsi</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Form Penerimaan">
            <form method="POST" action="{{ route($routePrefix . '.penerimaan.store', $distribusi) }}"
                enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="jumlah_diterima" class="block text-sm font-medium text-slate-700">Jumlah Diterima</label>
                        <input id="jumlah_diterima" name="jumlah_diterima" type="number" required min="1"
                            max="{{ $distribusi->jumlah_porsi }}"
                            value="{{ old('jumlah_diterima') }}"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('jumlah_diterima')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="waktu_diterima" class="block text-sm font-medium text-slate-700">Waktu Diterima</label>
                        <input id="waktu_diterima" name="waktu_diterima" type="datetime-local" required
                            value="{{ old('waktu_diterima', now()->format('Y-m-d\TH:i')) }}"
                            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('waktu_diterima')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="penerima_nama" class="block text-sm font-medium text-slate-700">Nama Penerima / PIC</label>
                    <input id="penerima_nama" name="penerima_nama" type="text" required maxlength="255"
                        value="{{ old('penerima_nama') }}" placeholder="Nama penanggung jawab penerimaan di sekolah"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @error('penerima_nama')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="foto_bukti" class="block text-sm font-medium text-slate-700">Foto Bukti (opsional)</label>
                    <input id="foto_bukti" name="foto_bukti" type="file" accept="image/jpeg,image/png,image/webp"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1 file:text-sm file:font-medium file:text-slate-700">
                    <p class="mt-1 text-xs text-slate-500">Format JPG, JPEG, PNG, atau WEBP maksimal 2 MB.</p>
                    @error('foto_bukti')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="catatan" class="block text-sm font-medium text-slate-700">Catatan</label>
                    <textarea id="catatan" name="catatan" rows="3" placeholder="Contoh: jumlah diterima kurang 10 porsi karena ..."
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('catatan') }}</textarea>
                    @error('catatan')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Simpan Penerimaan
                    </button>
                    <a href="{{ route($routePrefix . '.penerimaan.show', $distribusi) }}"
                        class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
                </div>
            </form>
        </x-card>
    </div>
@endsection