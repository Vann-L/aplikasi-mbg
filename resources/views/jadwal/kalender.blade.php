@extends('layouts.app')

@section('title', 'Kalender Operasional')
@section('subtitle', 'Jadwal operasional seluruh SPPG')

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
            <x-card title="Tambah Hari Libur Operasional">
                <form method="POST" action="{{ route('admin.jadwal.kalender.store') }}"
                    class="grid grid-cols-1 gap-3 sm:grid-cols-4">
                    @csrf
                    <div>
                        <label for="kal-tanggal" class="block text-xs font-medium text-slate-500">Tanggal</label>
                        <input id="kal-tanggal" name="tanggal" type="date" required
                            value="{{ old('tanggal') }}"
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                        @error('tanggal')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="kal-alasan" class="block text-xs font-medium text-slate-500">Alasan</label>
                        <input id="kal-alasan" name="alasan" type="text" maxlength="255"
                            value="{{ old('alasan', 'Libur nasional / cuti bersama') }}"
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label for="kal-catatan" class="block text-xs font-medium text-slate-500">Catatan</label>
                        <input id="kal-catatan" name="catatan" type="text" maxlength="255"
                            value="{{ old('catatan') }}"
                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                    </div>
                    <div class="flex items-end">
                        <input type="hidden" name="status" value="{{ \App\Models\KalenderOperasional::STATUS_LIBUR }}">
                        <button type="submit"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </x-card>
        @endif

        <x-card>
            <p class="mb-3 text-xs text-slate-500">
                Hari dengan status Libur menutup operasional SPPG dan memenangkan seluruh jadwal pegawai pada tanggal
                tersebut (tidak dapat absen, tidak dihitung alpa).
            </p>

            @if ($kalender->isEmpty())
                <x-empty-state title="Belum ada entri kalender"
                    description="Kalender operasional mencatat hari libur/buka seluruh SPPG. Entri baru hanya dibuat jika diperlukan." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-3 py-2 font-medium">Tanggal</th>
                                <th class="px-3 py-2 font-medium">Status</th>
                                <th class="px-3 py-2 font-medium">Alasan</th>
                                <th class="px-3 py-2 font-medium">Catatan</th>
                                @if ($canManage)
                                    <th class="px-3 py-2 text-right font-medium">Ubah Status</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($kalender as $item)
                                <tr>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->tanggal->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $item->status === 'libur' ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700' }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->alasan ?: '-' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $item->catatan ?: '-' }}</td>
                                    @if ($canManage)
                                        <td class="px-3 py-2">
                                            <div class="flex items-center justify-end gap-2">
                                                <form method="POST"
                                                    action="{{ route('admin.jadwal.kalender.update', $item) }}"
                                                    class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <select name="status" required
                                                        class="rounded-lg border border-slate-300 bg-white px-2 py-1 text-sm text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500">
                                                        @foreach (\App\Models\KalenderOperasional::STATUSES as $status)
                                                            <option value="{{ $status }}" @selected($item->status === $status)>{{ ucfirst($status) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="alasan" value="{{ $item->alasan }}">
                                                    <input type="hidden" name="catatan" value="{{ $item->catatan }}">
                                                    <button type="submit"
                                                        class="rounded-lg border border-slate-300 px-2 py-1 text-sm text-slate-600 hover:bg-slate-100">
                                                        Simpan
                                                    </button>
                                                </form>
                                                <form method="POST"
                                                    action="{{ route('admin.jadwal.kalender.destroy', $item) }}"
                                                    onsubmit="return confirm('Hapus entri kalender ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-700">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $kalender->links() }}
                </div>
            @endif
        </x-card>
    </div>
@endsection