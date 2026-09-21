@php
    $badges = [
        \App\Models\Distribusi::STATUS_DIJADWALKAN => 'bg-slate-100 text-slate-600',
        \App\Models\Distribusi::STATUS_DISIAPKAN => 'bg-amber-50 text-amber-700',
        \App\Models\Distribusi::STATUS_DIKIRIM => 'bg-blue-50 text-blue-700',
        \App\Models\Distribusi::STATUS_DITERIMA => 'bg-indigo-50 text-indigo-700',
        \App\Models\Distribusi::STATUS_SELESAI => 'bg-emerald-50 text-emerald-700',
    ];
    $labels = [
        \App\Models\Distribusi::STATUS_DIJADWALKAN => 'Dijadwalkan',
        \App\Models\Distribusi::STATUS_DISIAPKAN => 'Disiapkan',
        \App\Models\Distribusi::STATUS_DIKIRIM => 'Dikirim',
        \App\Models\Distribusi::STATUS_DITERIMA => 'Diterima',
        \App\Models\Distribusi::STATUS_SELESAI => 'Selesai',
    ];
    $badge = $badges[$status] ?? 'bg-slate-100 text-slate-600';
    $label = $labels[$status] ?? ucwords(str_replace('_', ' ', $status));
@endphp

<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $badge }}">{{ $label }}</span>