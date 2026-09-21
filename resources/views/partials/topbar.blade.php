@php($user = auth()->user())

<header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 sm:px-6" data-topbar>
    <div class="flex items-center gap-3">
        <button
            type="button"
            data-sidebar-toggle
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition-colors hover:bg-slate-100 lg:hidden"
            aria-label="Buka menu"
        >
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" d="M3 5.5h14M3 10h14M3 14.5h14" />
            </svg>
        </button>

        <div>
            <h1 class="text-sm font-semibold text-slate-800 sm:text-base">@yield('title', 'Dashboard')</h1>
            @hasSection('subtitle')
                <p class="hidden text-xs text-slate-500 sm:block">@yield('subtitle')</p>
            @endif
        </div>
    </div>

    <details class="relative">
        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-1.5 transition-colors hover:bg-slate-50 [&::-webkit-details-marker]:hidden">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
            <span class="hidden text-left sm:block">
                <span class="block text-sm font-medium text-slate-700">{{ $user->name }}</span>
                <span class="block text-xs capitalize text-slate-500">{{ $user->role }}</span>
            </span>
            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m6 8 4 4 4-4" />
            </svg>
        </summary>

        <div class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="truncate text-sm font-medium text-slate-800">{{ $user->name }}</p>
                <p class="truncate text-xs text-slate-500">{{ $user->email }}</p>
                <span class="mt-1 inline-block rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium capitalize text-emerald-700">
                    {{ $user->role }}
                </span>
            </div>

            <div class="p-1">
                <a href="{{ route('password.edit') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-600 transition-colors hover:bg-slate-100">
                    Ubah Password
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-red-600 transition-colors hover:bg-red-50">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </details>
</header>
