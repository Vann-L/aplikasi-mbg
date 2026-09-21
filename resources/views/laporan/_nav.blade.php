@php
    $items = [
        'produksi' => 'Produksi',
        'stok' => 'Stok',
        'distribusi' => 'Distribusi',
        'penerimaan' => 'Penerimaan',
        'absensi' => 'Absensi',
    ];
@endphp

<nav class="flex flex-wrap gap-2">
    @foreach ($items as $key => $label)
        <a
            href="{{ route($routePrefix . '.laporan.' . $key) }}"
            class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $active === $key ? 'bg-emerald-600 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-100' }}"
        >
            {{ $label }}
        </a>
    @endforeach
</nav>