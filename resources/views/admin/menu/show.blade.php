@extends('layouts.app')

@section('title', 'Detail Menu')
@section('subtitle', 'Master data menu MBG')

@section('content')
    <div class="mx-auto max-w-2xl">
        <x-card title="Detail Menu">
            @if ($menu->fotoUrl())
                <img src="{{ $menu->fotoUrl() }}" alt="{{ $menu->nama }}"
                    class="mb-4 max-h-64 w-full rounded-xl border border-slate-200 object-cover">
            @endif

            <dl class="space-y-3 text-sm">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Nama</dt>
                    <dd class="font-medium text-slate-800">{{ $menu->nama }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Deskripsi</dt>
                    <dd class="text-right text-slate-800">{{ $menu->deskripsi ?: '-' }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Status</dt>
                    <dd>
                        <span
                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $menu->status === \App\Models\Menu::STATUS_ACTIVE ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                            {{ $menu->status === \App\Models\Menu::STATUS_ACTIVE ? 'Active' : 'Inactive' }}
                        </span>
                    </dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-slate-500">Produksi Terkait</dt>
                    <dd class="text-slate-800">{{ $menu->produksis()->count() }} produksi</dd>
                </div>
            </dl>

            <div class="mt-6 flex items-center gap-4">
                <a href="{{ route('admin.menu.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
                    Kembali
                </a>

                @if (auth()->user()->role === 'admin')
                    <a href="{{ route('admin.menu.edit', $menu) }}"
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Edit
                    </a>
                @endif
            </div>
        </x-card>
    </div>
@endsection
