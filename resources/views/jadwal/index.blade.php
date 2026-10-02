@extends('layouts.app')

@section('title', 'Override Tanggal')
@section('subtitle', 'Penyesuaian jadwal untuk tanggal tertentu')

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

        <div class="no-print">
            @include('jadwal._nav', ['routePrefix' => $routePrefix, 'active' => $active])
        </div>

        @if ($canManage)
            <x-card title="Tukar Jadwal">
                <form method="POST" action="{{ route('admin.jadwal.tukar') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    @csrf
                    <div>
                        <label for="tukar-tanggal" class="block text-xs font-medium text-slate-500">Tanggal</label>
                        <input id="tukar-tanggal" name="tanggal" type="date" required
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('tanggal')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="tukar-pegawai-a" class="block text-xs font-medium text-slate-500">Pegawai A</label>
                        <select id="tukar-pegawai-a" name="pegawai_a" required
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">Pilih pegawai</option>
                            @foreach (\App\Models\Pegawai::query()->where('status', \App\Models\Pegawai::STATUS_ACTIVE)->orderBy('nama')->get() as $pegawai)
                                <option value="{{ $pegawai->id }}" @selected(old('pegawai_a') == $pegawai->id)>{{ $pegawai->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="tukar-pegawai-b" class="block text-xs font-medium text-slate-500">Pegawai B</label>
                        <select id="tukar-pegawai-b" name="pegawai_b" required
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">Pilih pegawai</option>
                            @foreach (\App\Models\Pegawai::query()->where('status', \App\Models\Pegawai::STATUS_ACTIVE)->orderBy('nama')->get() as $pegawai)
                                <option value="{{ $pegawai->id }}" @selected(old('pegawai_b') == $pegawai->id)>{{ $pegawai->nama }}</option>
                            @endforeach
                        </select>
                        @error('pegawai_b')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Tukar
                        </button>
                        <p class="text-xs text-slate-500 leading-tight">Kedua pegawai harus memiliki jadwal efektif
                            (terjadwal) pada tanggal tersebut.</p>
                    </div>
                </form>
            </x-card>
        @endif

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route($routePrefix . '.jadwal.index') }}"
                class="flex flex-col gap-2 sm:flex-row">
                <input type="date" name="tanggal" value="{{ $tanggal }}"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                <select name="pegawai_id"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Semua pegawai</option>
                    @foreach ($pegawaiList as $pegawai)
                        <option value="{{ $pegawai->id }}" @selected($pegawaiId === $pegawai->id)>{{ $pegawai->nama }}</option>
                    @endforeach
                </select>
                <select name="status"
                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\JadwalPegawai::STATUSES as $item)
                        <option value="{{ $item }}" @selected($status === $item)>{{ ucwords($item) }}</option>
                    @endforeach
                </select>
                <button type="submit"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Cari
                </button>
            </form>

            @if ($canManage)
                <a href="{{ route($routePrefix . '.jadwal.create') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Tambah Override
                </a>
            @endif
        </div>

        <x-card>
            @if ($jadwal->isEmpty())
                <x-empty-state title="Belum ada override"
                    description="Override tanggal berisi penyesuaian jadwal untuk tanggal tertentu. Pola mingguan diatur di tab Jadwal Mingguan." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Tanggal</th>
                                <th class="px-3 py-2 font-medium">Pegawai</th>
                                <th class="px-3 py-2 font-medium">Jam Masuk</th>
                                <th class="px-3 py-2 font-medium">Jam Pulang</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                                <th class="px-3 py-2 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($jadwal as $item)
                                <tr>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->tanggal->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ $item->pegawai->nama }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->jam_masuk?->format('H:i') ?? '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->jam_pulang?->format('H:i') ?? '-' }}</td>
                                    <td class="px-3 py-2">@include('jadwal._status_badge', ['status' => $item->status])</td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route($routePrefix . '.jadwal.show', $item) }}"
                                                class="text-slate-600 hover:text-slate-900">Detail</a>

                                            @if ($canManage)
                                                <a href="{{ route($routePrefix . '.jadwal.edit', $item) }}"
                                                    class="text-emerald-700 hover:text-emerald-800">Edit</a>

                                                <form method="POST"
                                                    action="{{ route($routePrefix . '.jadwal.destroy', $item) }}"
                                                    onsubmit="return confirm('Hapus override jadwal ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-700">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $jadwal->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection