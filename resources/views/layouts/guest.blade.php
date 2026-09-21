<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Masuk') - {{ config('app.name') }}</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 text-slate-800 antialiased">
        <main class="w-full max-w-sm">
            <div class="mb-6 flex items-center justify-center gap-2">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-sm font-bold text-white">MB</span>
                <div class="leading-tight">
                    <p class="text-sm font-semibold text-slate-800">SPPG</p>
                    <p class="text-xs text-slate-500">Pengelolaan MBG</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @yield('content')
            </div>
        </main>
    </body>
</html>
