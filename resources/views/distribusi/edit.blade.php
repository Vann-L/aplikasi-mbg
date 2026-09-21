@extends('layouts.app')

@section('title', 'Jadwalkan Distribusi')
@section('subtitle', 'Atur petugas, kendaraan, tanggal, dan jam berangkat')

@section('content')
    <x-card title="Penjadwalan Distribusi {{ $distribusi->kode_distribusi }}">
        <form method="POST" action="{{ route('admin.distribusi.update', $distribusi) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="petugas_id" class="block text-sm font-medium text-slate-700">Petugas</label>
                <select id="petugas_id" name="petugas_id"
                    class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Belum ditentukan</option>
                    @foreach ($petugasList as $pegawai)
                        <option value="{{ $pegawai->id }}"
                            @selected(old('petugas_id', $distribusi->petugas_id) === $pegawai->id)>
                            {{ $pegawai->nama }} ({{ $pegawai->jabatan }})
                        </option>
                    @endforeach
                </select>
                @error('petugas_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="kendaraan" class="block text-sm font-medium text-slate-700">Kendaraan</label>
                <input id="kendaraan" name="kendaraan" type="text"
                    value="{{ old('kendaraan', $distribusi->kendaraan) }}" placeholder="Contoh: Mobil Box 01"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                @error('kendaraan')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="tanggal" class="block text-sm font-medium text-slate-700">Tanggal</label>
                    <input id="tanggal" name="tanggal" type="date" required
                        value="{{ old('tanggal', $distribusi->tanggal->format('Y-m-d')) }}"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @error('tanggal')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="jam_berangkat" class="block text-sm font-medium text-slate-700">Jam Berangkat</label>
                    <input id="jam_berangkat" name="jam_berangkat" type="time"
                        value="{{ old('jam_berangkat', $distribusi->jam_berangkat?->format('H:i')) }}"
                        class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    @error('jam_berangkat')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="catatan" class="block text-sm font-medium text-slate-700">Catatan</label>
                <textarea id="catatan" name="catatan" rows="3"
                    class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">{{ old('catatan', $distribusi->catatan) }}</textarea>
                @error('catatan')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Simpan Penjadwalan
                </button>
                <a href="{{ route('admin.distribusi.show', $distribusi) }}"
                    class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
            </div>
        </form>
    </x-card>
@endsection