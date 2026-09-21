@php
    $statusLabels = [
        \App\Models\Produksi::STATUS_BELUM_DIMULAI => 'Belum Dimulai',
        \App\Models\Produksi::STATUS_PERSIAPAN => 'Persiapan',
        \App\Models\Produksi::STATUS_PENGOLAHAN => 'Pengolahan',
        \App\Models\Produksi::STATUS_QC => 'QC',
        \App\Models\Produksi::STATUS_PEMORSIAN => 'Pemorsian',
        \App\Models\Produksi::STATUS_PACKING => 'Packing',
        \App\Models\Produksi::STATUS_SELESAI => 'Selesai',
    ];

    $statusBadges = [
        \App\Models\Produksi::STATUS_BELUM_DIMULAI => 'bg-slate-100 text-slate-600',
        \App\Models\Produksi::STATUS_PERSIAPAN => 'bg-blue-50 text-blue-700',
        \App\Models\Produksi::STATUS_PENGOLAHAN => 'bg-indigo-50 text-indigo-700',
        \App\Models\Produksi::STATUS_QC => 'bg-purple-50 text-purple-700',
        \App\Models\Produksi::STATUS_PEMORSIAN => 'bg-cyan-50 text-cyan-700',
        \App\Models\Produksi::STATUS_PACKING => 'bg-amber-50 text-amber-700',
        \App\Models\Produksi::STATUS_SELESAI => 'bg-emerald-50 text-emerald-700',
    ];
@endphp

<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusBadges[$status] ?? 'bg-slate-100 text-slate-600' }}">
    {{ $statusLabels[$status] ?? $status }}
</span>