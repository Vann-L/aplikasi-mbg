@props(['title' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white shadow-sm']) }}>
    @if ($title)
        <header class="border-b border-slate-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">{{ $title }}</h2>
        </header>
    @endif

    <div class="p-4">
        {{ $slot }}
    </div>
</section>
