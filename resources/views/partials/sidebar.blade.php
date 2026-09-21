@php
    $role = auth()->user()->role;
    $items = config("navigation.roles.{$role}", []);
@endphp

<aside
    data-sidebar
    class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-200 ease-out lg:translate-x-0"
>
    <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-5">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-sm font-bold text-white">MB</span>
        <div class="leading-tight">
            <p class="text-sm font-semibold text-slate-800">SPPG</p>
            <p class="text-xs text-slate-500">Pengelolaan MBG</p>
        </div>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        @foreach ($items as $item)
            @php($active = request()->routeIs($item['route']))
            <a
                href="{{ route($item['route']) }}"
                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ $active ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
            >
                <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $active ? 'bg-emerald-600' : 'bg-slate-300' }}"></span>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-slate-200 px-5 py-3">
        <p class="text-xs text-slate-400">Versi Awal</p>
    </div>
</aside>

<div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden"></div>
