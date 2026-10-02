# Sistem Informasi Pengelolaan dan Distribusi MBG pada SPPG

Aplikasi web internal untuk satu SPPG (Sentra Pemenuhan Gizi) yang mengelola
alur: bahan baku → stok → produksi → pemorsian → packing → distribusi →
penerimaan sekolah, ditambah manajemen pegawai, jadwal kerja, absensi QR, dan
laporan.

Dokumentasi lengkap: [`docs/PROJECT_SPEC.md`](docs/PROJECT_SPEC.md).

## Persyaratan

- PHP 8.3.28
- Laravel Framework 13.32.0
- Composer
- Node.js + npm (untuk frontend)
- Database: MySQL/MariaDB (default) — test suite memakai SQLite in-memory

## Instalasi

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Atur koneksi database di `.env` (lihat `.env.example`). Konfigurasi penting:

- `APP_TIMEZONE=Asia/Jakarta` — zona waktu aplikasi, jangan diubah tanpa alasan
- `DB_*` — koneksi database
- `APP_URL` — dipakai untuk tautan scan QR absensi

Migrasi dan seed:

```bash
php artisan migrate
php artisan db:seed
```

`DatabaseSeeder` memanggil `DemoDataSeeder` hanya di environment
`local`/`development`. Seeder demo bersifat idempotent (bisa diulang), menolak
berjalan di `production`, dan hanya menyentuh data bertanda `[DEMO]` — data
real tidak pernah dihapus.

## Akun Demo

Semua akun ber-password `password`:

| Email             | Role    | Keterangan                      |
| ----------------- | ------- | ------------------------------- |
| `admin@mbg.test`  | admin   | Administrator SPPG (tanpa pegawai) |
| `kepala@mbg.test` | kepala  | Terhubung ke pegawai DEMO-K001  |
| `petugas@mbg.test`| petugas | Terhubung ke pegawai DEMO-K003  |

## Menjalankan

```bash
php artisan serve
npm run dev     # atau: npm run build
```

Login mengarahkan ke area sesuai role (`admin.dashboard`, `kepala.dashboard`,
`petugas.dashboard`). Frontend yang belum ter-build bisa memicu error
"Unable to locate file in Vite manifest" — jalankan `npm run build` dulu.

## Role

Tiga role login, terpisah dari jabatan/bagian kerja pegawai:

- **admin** (`role=admin`): kelola master data, stok, produksi, distribusi,
  penerimaan, jadwal, absensi, laporan, dan **User Management**.
- **kepala** (`role=kepala`): pemantauan read-only + beberapa kelola.
- **petugas** (`role=petugas`): dashboard, stok, jadwal saya, absensi QR.

Middleware `role` melindungi tiap area; role tidak sesuai → 403.

## Jadwal Kerja (jadwal efektif)

Jadwal efektif = kombinasi dengan prioritas tetap:

```
Kalender Operasional (libur SPPG)
  → Override per tanggal  (jadwal_pegawai)
    → Pola mingguan       (pola_jadwal)
      → Tanpa pola        (tidak terjadwal)
```

Seluruh kebutuhan "jam kerja" (absensi, rekap, laporan, dashboard, "Jadwal
Saya") memakai satu sumber: `JadwalEfektifService`. Status hanya `terjadwal`
/ `izin` / `libur`; `terlambat`/`alpa` ditentukan dari perbandingan jadwal
dengan data absensi, bukan dari jadwal.

## User Management

Menu **Pengguna** (khusus admin, `admin.user.*`): daftar, buat akun, edit,
nonaktifkan, dan reset password manual. Setiap akun dapat dihubungkan ke satu
pegawai (`pegawai.user_id`, nullable & unique). Admin tidak dapat
menonaktifkan akun sendiri. Tidak ada registrasi mandiri/forgot password.

## Absensi QR (development)

Alur: petugas login → menu **Absensi** → `petugas.absensi.index` menampilkan
jadwal efektif hari ini + form token. `AbsensiController::qr()` / `token()`
membuat **token dinamis berumur pendek** (di-refresh otomatis, tanpa menyimpan
ribuan record) yang di-encode sebagai QR menuju `petugas.absensi.scan-token`.

Saat pengujian manual:

1. Jalankan `php artisan serve --host=0.0.0.0` dan set `APP_URL` ke
   `http://<ip-LAN>:8000` agar bisa diakses HP di jaringan yang sama.
2. Login sebagai `petugas@mbg.test`, buka halaman Absensi, pindai QR dari
   kamera HP (atau tempel token di form).
3. Absensi masuk/pulang. Scan ditolak ramah bila tidak ada jadwal, hari izin,
   atau hari libur (berbasis jadwal efektif).

## Testing & Formatting

```bash
php artisan test --compact   # seluruh suite (SQLite in-memory)
vendor/bin/pint --dirty      # format ulang file yang berubah
```

## Lisensi / Catatan

Proyek internal sesuai spesifikasi `docs/PROJECT_SPEC.md`. Fitur di luar
scope MVP (super admin, multi-SPPG, data siswa, notifikasi, audit log, dan
lain-lain) tidak dibangun tanpa instruksi eksplisit.