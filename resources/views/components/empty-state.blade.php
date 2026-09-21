@props(['title' => 'Belum ada data', 'description' => null])

<div class="rounded-lg border border-dashed border-slate-300 px-6 py-10 text-center">
    <p class="text-sm font-medium text-slate-600">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 text-xs text-slate-400">{{ $description }}</p>
    @endif
</div>
