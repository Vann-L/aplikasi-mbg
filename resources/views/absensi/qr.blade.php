@extends('layouts.app')

@section('title', 'Display QR Absensi')
@section('subtitle', 'Pegawai memindai QR untuk absen masuk dan pulang')

@section('content')
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <x-card title="QR Absensi">
                <div class="flex flex-col items-center space-y-4">
                    <div class="rounded-xl border-2 border-slate-200 bg-white p-4">
                        <img id="qr-img" src="{{ route($routePrefix . '.absensi.qr-img') }}?token={{ $token }}"
                            alt="QR absensi" width="300" height="300" class="h-72 w-72 object-contain">
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-slate-500">Token diperbarui setiap</p>
                        <p class="text-3xl font-bold text-slate-800">
                            <span id="qr-countdown">{{ $expirySeconds }}</span><span class="ml-1 text-base font-medium text-slate-500">detik</span>
                        </p>
                    </div>
                    <p id="qr-token" class="max-w-full break-all rounded-lg bg-slate-100 px-3 py-2 font-mono text-xs text-slate-600">{{ $token }}</p>
                </div>
            </x-card>

            <x-card title="Cara penggunaan">
                <ol class="list-decimal space-y-2 pl-5 text-sm text-slate-600">
                    <li>Pegawai membuka aplikasi kamera ponsel dan mengarahkannya ke QR di sebelah kiri.</li>
                    <li>Saat pendeteksi QR muncul, pegawai menekan link yang terdeteksi.</li>
                    <li>Halaman absensi terbuka dan absen masuk / pulang tercatat otomatis.</li>
                    <li>Token QR berganti setiap beberapa detik untuk mencegah pemindaian ulang. Bila token terbaca kedaluwarsa, minta pegawai memindai lagi pada QR terbaru.</li>
                    <li>Pegawai tanpa jadwal atau berstatus izin / libur pada hari itu tidak dapat melakukan absensi normal.</li>
                </ol>

                <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-slate-500">Fallback input manual</p>
                    <p class="text-xs text-slate-600">
                        Bila ponsel tidak dapat membaca QR, minta pegawai membuka menu
                        <span class="font-medium text-slate-800">Absensi</span> pada aplikasi petugas lalu memasukkan
                        token berikut ini.
                    </p>
                </div>
            </x-card>
        </div>

        <a href="{{ route($routePrefix . '.absensi.index') }}" class="text-sm text-slate-600 hover:text-slate-900">
            Kembali ke Rekap Absensi
        </a>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            let countdown = Number('{{ $expirySeconds }}');
            const tokenUrl = '{{ route($routePrefix . '.absensi.token') }}';
            const qrImgUrl = (token) => '{{ route($routePrefix . '.absensi.qr-img') }}?token=' + encodeURIComponent(token);

            function refresh() {
                fetch(tokenUrl, { headers: { 'Accept': 'application/json' } })
                    .then((response) => response.json())
                    .then((data) => {
                        const image = document.getElementById('qr-img');
                        const token = document.getElementById('qr-token');
                        image.src = qrImgUrl(data.token);
                        token.textContent = data.token;
                        countdown = data.expires_in;
                        render();
                    })
                    .catch(() => {});
            }

            function render() {
                document.getElementById('qr-countdown').textContent = countdown;
            }

            setInterval(() => {
                countdown -= 1;
                if (countdown <= 0) {
                    refresh();
                    return;
                }
                render();
            }, 1000);

            refresh();
        })();
    </script>
@endpush