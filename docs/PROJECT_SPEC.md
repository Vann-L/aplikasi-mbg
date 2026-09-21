# Spesifikasi Project

## Sistem Informasi Pengelolaan dan Distribusi MBG pada SPPG

---

## 1. Project

**Nama:**
Sistem Informasi Pengelolaan dan Distribusi MBG pada SPPG

**Tujuan:**
Membangun aplikasi web internal untuk membantu satu SPPG mengelola operasional
Makan Bergizi Gratis, mulai dari bahan baku, stok, produksi, distribusi ke
sekolah, penerimaan, hingga absensi pegawai.

## 2. Scope

- Sistem hanya digunakan untuk 1 SPPG.
- Aplikasi berbasis web.
- Fokus utama adalah operasional dan pengelolaan SPPG.
- Sekolah hanya menjadi tujuan distribusi.
- Siswa/penerima manfaat tidak memiliki akun.
- Tidak ada pencatatan penerimaan makanan per siswa.
- Tidak ada data siswa individual pada MVP.

## 3. Role Login

Hanya ada 3 role:

### admin

Admin SPPG.

Tanggung jawab:
- Mengelola user
- Mengelola pegawai
- Mengelola sekolah
- Mengelola menu
- Mengelola bahan baku
- Mengelola data stok
- Mengakses data operasional
- Mengakses laporan

### kepala

Kepala SPPG.

Tanggung jawab:
- Dashboard
- Monitoring produksi
- Monitoring stok
- Monitoring distribusi
- Monitoring penerimaan
- Monitoring absensi
- Melihat laporan
- Approval tertentu jika nanti memang diperlukan

### petugas

Petugas SPPG.

Tanggung jawab:
- Dashboard
- Absensi
- Produksi
- Pemorsian
- Packing
- Distribusi
- Penerimaan

**PENTING:**
Role login berbeda dengan jabatan dan bagian kerja.

Contoh:
User dapat memiliki role "petugas" tetapi jabatan "Staff Operasional"
dan bagian "Distribusi".

## 4. Authentication

MVP hanya membutuhkan:

- Login
- Logout
- Ubah password
- Admin dapat melakukan reset password user secara manual

Belum diperlukan:

- Forgot password via email
- OTP
- Email reset password
- Mailpit untuk reset password

Fitur tersebut dapat ditambahkan pada tahap berikutnya jika diperlukan.

Implementasi Phase 2:

- Guard `web` berbasis session; provider `users` (Eloquent `App\Models\User`).
- `users.status` bernilai `active` atau `inactive`. User `inactive` ditolak saat login.
- Login berhasil mengarahkan ke area sesuai role: `admin.dashboard`,
  `kepala.dashboard`, `petugas.dashboard`.
- Middleware `role` melindungi tiap area role (role tidak sesuai → 403).
- Ubah password sendiri tersedia untuk semua role (`password.edit`, `password.update`).
- Admin dapat mereset password user (`admin.users.reset-password`, khusus role admin).
- Tanpa "remember me", tanpa verifikasi email, tanpa audit log.

## 5. Fitur yang TIDAK termasuk MVP

Jangan membuat atau merancang implementasi untuk:

- Super Admin
- Multi-SPPG
- Login siswa
- Data siswa individual
- Absensi siswa
- Foto setiap siswa
- Modul keuangan kompleks
- Supplier management kompleks
- Audit log/activity log
- Notifikasi
- GPS tracking
- OTP
- Forgot password via email
- Fitur tambahan yang belum disepakati

Jangan menambahkan fitur di luar scope tanpa instruksi eksplisit.

## 6. Master Data

### Pegawai

Data:
- id
- user_id
- id_pegawai
- nama
- jabatan
- bagian
- no_hp
- status

Implementasi Phase 4.1 (CRUD Pegawai):

- `pegawai.status` bernilai `active` atau `inactive`; migration
  `2026_09_17_200637_normalize_pegawai_status_on_pegawai_table` menormalkan data
  lama dan mengubah default (sebelumnya `aktif`).
- Rute resource `admin.pegawai.*`: index, create, store, show, edit, update,
  destroy; parameter `{pegawai}` dibatasi angka agar tidak bentrok dengan
  `/pegawai/create`.
- Baca (index, show): role `admin` dan `kepala`. Tulis (create/store/edit/
  update/destroy): hanya role `admin`.
- `user_id` tidak diisi lewat CRUD pegawai; akun ditampilkan sebagai
  "Belum terhubung" bila belum ada relasi.
- Index mendukung pencarian (nama/id_pegawai/jabatan), filter status, dan
  pagination 10 per halaman.
- Pegawai yang masih memiliki jadwal atau absensi tidak dapat dihapus.

### Sekolah

Data:
- id
- npsn
- nama
- alamat
- kontak
- jumlah_penerima
- status

**PENTING:**
`jumlah_penerima` adalah jumlah penerima MBG di sekolah, bukan data individual
siswa.

Tidak perlu tabel siswa untuk MVP.

Implementasi Phase 4.2 (CRUD Sekolah):

- `sekolah.status` bernilai `active` atau `inactive`; migration
  `2026_09_17_201044_normalize_sekolah_status_on_sekolah_table` menormalkan data
  lama dan mengubah default (sebelumnya `aktif`).
- Rute resource `admin.sekolah.*`: index, create, store, show, edit, update,
  destroy; parameter `{sekolah}` dibatasi angka agar tidak bentrok dengan
  `/sekolah/create`.
- Baca (index, show): role `admin`, `kepala`, dan `petugas` (kebutuhan
  operasional distribusi). Tulis (create/store/edit/update/destroy): hanya role
  `admin`.
- Index mendukung pencarian (nama/NPSN), filter status, dan pagination 10 per
  halaman. `jumlah_penerima` ditampilkan jelas sebagai data referensi.
- Sekolah yang sudah direferensikan distribusi tidak dapat dihapus (guard, bukan
  cascade delete) agar histori distribusi tetap aman.
- Bahan Baku masih berupa placeholder.

### Menu

Data:
- id
- nama
- deskripsi
- foto (nullable)
- status

Implementasi Phase 4.3 (CRUD Menu):

- `menus.status` bernilai `active` atau `inactive`; migration
  `2026_09_17_201347_normalize_menu_status_on_menus_table` menormalkan data lama
  dan mengubah default (sebelumnya `aktif`).
- Rute resource `admin.menu.*`: index, create, store, show, edit, update,
  destroy; parameter `{menu}` dibatasi angka agar tidak bentrok dengan
  `/menu/create`.
- Baca (index, show): role `admin`, `kepala`, dan `petugas`. Tulis (create/store/
  edit/update/destroy): hanya role `admin`.
- Index mendukung pencarian nama, filter status, pagination 10 per halaman, dan
  thumbnail foto bila tersedia.
- Foto disimpan memakai Laravel Storage pada disk `public` di direktori
  `menu-foto`; database hanya menyimpan path. Format jpg/jpeg/png/webp, maksimal
  2 MB. Foto lama dihapus saat diganti dan saat menu dihapus.
- Menu yang sudah direferensikan produksi tidak dapat dihapus (guard, bukan
  cascade delete) agar histori produksi tetap aman.
- Menu TIDAK memiliki target porsi.

### Bahan Baku

Data:
- id
- nama
- satuan
- stok_minimum
- status

Implementasi Phase 4.4 (CRUD Bahan Baku):

- `bahan_baku.status` bernilai `active` atau `inactive`; migration
  `2026_09_17_201941_normalize_bahan_baku_status_on_bahan_baku_table` menormalkan
  data lama dan mengubah default (sebelumnya `aktif`).
- Rute resource `admin.bahan-baku.*`: index, create, store, show, edit, update,
  destroy; parameter `{bahan_baku}` dibatasi angka agar tidak bentrok dengan
  `/bahan-baku/create`.
- Baca (index, show): role `admin`, `kepala`, dan `petugas`. Tulis (create/store/
  edit/update/destroy): hanya role `admin`.
- Index mendukung pencarian nama, filter status, dan pagination 10 per halaman.
- Satuan berupa input teks bebas dengan saran (datalist) kg/gram/liter/pcs/box;
  tidak ada master data satuan terpisah.
- `stok_minimum` (decimal) adalah batas indikator stok rendah. Perhitungan stok
  tersedia tidak diimplementasikan pada phase ini (Phase 5).
- Bahan baku yang sudah dipakai `produksi_bahan` atau `stok_mutations` tidak dapat
  dihapus (guard, bukan cascade delete).

## 7. Stok

Gunakan konsep mutasi stok.

Jenis mutasi:
- masuk
- keluar
- penyesuaian

Data mutasi:
- bahan_baku_id
- tipe
- jumlah
- tanggal
- keterangan
- user_id

Stok tersedia dihitung berdasarkan mutasi.

Jangan membuat sistem stok yang terlalu kompleks pada MVP.

> **Implementasi (Phase 5):**
> - Stok tersedia = `SUM(mutasi)` dengan `masuk` +jumlah, `keluar` −jumlah,
>   dan `penyesuaian` dihitung sesuai tanda (positif menambah, negatif mengurangi).
>   Tidak ada kolom `stok_sekarang`.
> - Akses: Admin dapat melihat stok, mencatat semua tipe mutasi, dan melihat riwayat.
>   Petugas dapat melihat stok serta mencatat `masuk`/`keluar` (bukan penyesuaian).
>   Kepala hanya melihat stok dan riwayat (tanpa perubahan).
> - Indikator status stok: `Habis` (≤ 0), `Rendah` (0 < stok < stok_minimum),
>   `Normal` (≥ stok_minimum).
> - Stok tidak boleh negatif: `keluar` yang melebihi stok tersedia ditolak, dan
>   `penyesuaian` tidak boleh membuat stok negatif. Penyesuaian tidak boleh bernilai 0.
> - Riwayat mutasi dipaginasi; kolom: tanggal, bahan, tipe, jumlah, keterangan,
>   dan pencatat (user).

## 8. Produksi

Produksi berkaitan dengan menu dan kebutuhan distribusi.

Produksi dimulai dari rencana distribusi: admin/petugas menentukan menu dan
tanggal produksi, lalu membuat rencana distribusi berdasarkan sekolah tujuan.
Total porsi dari rencana distribusi menjadi kebutuhan produksi, yang kemudian
menjadi target produksi. Lihat §13 untuk workflow lengkap.

Status produksi:
- belum_dimulai
- persiapan
- pengolahan
- qc
- pemorsian
- packing
- selesai

Field utama:
- menu_id
- tanggal
- target_porsi
- hasil_porsi
- status
- catatan
- started_at
- completed_at
- created_by

**PENTING:**
`target_porsi` TIDAK boleh menjadi angka bebas yang tidak berkaitan dengan
distribusi.

- `target_porsi` = total kebutuhan porsi distribusi = total `jumlah_porsi` dari
  seluruh distribusi yang terkait dengan produksi.
- `hasil_porsi` = jumlah produksi aktual, dicatat setelah produksi selesai.
- Sistem harus dapat menghitung selisih produksi: kekurangan atau kelebihan.

Contoh:

```
Sekolah A = 900 porsi
Sekolah B = 700 porsi
Sekolah C = 400 porsi

Total kebutuhan = 2.000 porsi.
```

Maka target produksi = 2.000 porsi.

Jika hasil produksi = 1.980:

```
kebutuhan  = 2.000
hasil      = 1.980
kekurangan = 20
```

Jika hasil produksi = 2.010, maka kelebihan = 10.

Produksi menggunakan tabel penghubung `produksi_bahan` untuk mencatat bahan
yang digunakan.

> **Implementasi (Phase 6):**
> - Produksi dibuat dari menu aktif + tanggal (unik per kombinasi menu+tanggal).
> - Rencana distribusi ditambahkan per sekolah; total `jumlah_porsi` dihitung
>   menjadi kebutuhan porsi dan disinkronkan ke `target_porsi` setiap kali
>   rencana ditambah/diubah/dihapus (`Produksi::perbaruiTargetPorsi()`). `target_porsi`
>   tidak pernah diisi manual.
> - `produksi_bahan` mencatat bahan dengan `satuan` disalin dari bahan baku.
> - Mulai produksi (`belum_dimulai` → `persiapan`) memvalidasi stok seluruh bahan,
>   lalu dalam satu transaksi membuat `StokMutation` `keluar` per bahan dan mengisi
>   `started_at` + `stock_deducted_at` (penanda pemotongan, agar stok hanya
>   dipotong sekali). Jika ada bahan yang stoknya kurang, seluruh proses ditolak.
> - Status maju berurutan satu langkah (persiapan → pengolahan → qc → pemorsian →
>   packing → selesai); `completed_at` diisi saat mencapai `selesai`.
> - `hasil_porsi` dicatat terpisah; selisih = hasil − kebutuhan (`Produksi::selisihPorsi()`).
> - Rencana distribusi dan bahan hanya dapat diubah/dihapus selama `belum_dimulai`
>   (guard via FormRequest dan status produksi).
> - Akses: Admin membuat/mengubah/menghapus; Petugas membuat dan menjalankan tahapan;
>   Kepala hanya melihat. Produksi yang sudah punya distribusi, bahan, atau pemotongan
>   stok tidak dapat dihapus.

## 9. Distribusi

Admin/petugas membuat rencana distribusi berdasarkan sekolah tujuan sebelum
produksi berjalan. Satu menu dapat direncanakan untuk didistribusikan ke banyak
sekolah.

Contoh:

```
- Menu Nasi Ayam
- Sekolah A = 900
- Sekolah B = 700
- Sekolah C = 400
```

Setiap distribusi memiliki record sendiri dan memiliki SATU sekolah tujuan dan
SATU `jumlah_porsi`.

Data distribusi:
- id
- kode_distribusi
- produksi_id
- sekolah_id
- tanggal
- jumlah_porsi
- petugas_id
- kendaraan
- jam_berangkat
- status
- catatan
- created_by

Status:
- dijadwalkan
- disiapkan
- dikirim
- diterima
- selesai

Total `jumlah_porsi` dari seluruh distribusi yang terkait dengan sebuah produksi
menjadi kebutuhan produksi, dan `target_porsi` produksi diambil dari total
kebutuhan tersebut (§8).

Satu menu dapat memiliki banyak distribusi.

> **Implementasi (Phase 7):**
> - Pembuatan rencana distribusi tetap lewat detail produksi (Phase 6); Phase 7
>   hanya menangani pelaksanaan pengiriman (penjadwalan + status).
> - Penjadwalan (edit): admin menentukan `petugas`, `kendaraan` (teks bebas,
>   tanpa master armada), `tanggal`, `jam_berangkat` (opsional). Hanya dapat
>   diubah selama status `dijadwalkan`/`disiapkan`.
> - Petugas harus pegawai aktif yang memiliki akun aktif role `petugas`.
> - Status berjalan berurutan: `dijadwalkan` → siapkan → `disiapkan` → kirim →
>   `dikirim`. Status `diterima`/`selesai` menyusul pada Phase 8.
> - Kirim hanya diizinkan jika produksi telah memenuhi kebutuhan
>   (`hasil_porsi >= jumlah_porsi seluruh distribusi produksi`, tidak ada partial
>   shipment) dan petugas sudah ditentukan; `jam_berangkat` diisi otomatis saat
>   kirim bila belum dijadwalkan.
> - Akses: Admin melihat, menjadwalkan, mengubah status, dan menghapus;
>   Petugas hanya melihat dan menjalankan status pada distribusi yang ditugaskan
>   kepadanya; Kepala read-only.
> - Hapus hanya diizinkan untuk status `dijadwalkan`/`disiapkan`; setelah
>   `dikirim`/`diterima`/`selesai` diblokir (tanpa cascade).
> - Index: filter tanggal + status, pagination 15.

## 10. Penerimaan

Satu distribusi memiliki satu pencatatan penerimaan.

Data:
- distribusi_id
- jumlah_diterima
- waktu_diterima
- penerima_nama
- foto_bukti (nullable)
- catatan
- created_by

Penerimaan dilakukan pada level pengiriman/distribusi, BUKAN per siswa.

Foto bukti jika digunakan hanya satu foto untuk satu distribusi, bukan satu
foto untuk setiap siswa.

> **Implementasi (Phase 8):**
> - Penerimaan dibuat pada level distribusi; hanya satu penerimaan per
>   distribusi (enforced oleh unique index `distribusi_id`).
> - Penerimaan hanya dapat dicatat untuk distribusi berstatus `dikirim`. Setelah
>   penerimaan tercatat, status distribusi otomatis menjadi `diterima`.
> - `jumlah_diterima` wajib > 0 dan tidak boleh melebihi `distribusi.jumlah_porsi`;
>   jumlah lebih kecil tetap diperbolehkan dan selisih
>   (`dikirim - diterima`) ditampilkan pada detail serta dapat dijelaskan lewat
>   catatan.
> - Distribusi `diterima` dapat difinalisasi menjadi `selesai` (admin atau
>   petugas yang ditugaskan). Distribusi `selesai` tidak dapat menerima
>   pencatatan baru.
> - `foto_bukti` opsional (satu per penerimaan): JPG/JPEG/PNG/WEBP maksimal 2 MB,
>   disimpan di storage `public` (Laravel Storage), database hanya menyimpan path.
> - Tidak ada alur update/delete penerimaan pada MVP — data penerimaan adalah
>   histori operasional (bukan cascade delete).
> - Akses: Admin melihat, mencatat, dan menyelesaikan; Petugas hanya pada
>   distribusi yang ditugaskan; Kepala read-only.
> - Route: `admin.penerimaan.*`, `petugas.penerimaan.*` (index, show, create,
>   store, selesai), `kepala.penerimaan.*` (index, show).

## 11. Absensi

Absensi hanya untuk pegawai SPPG.

Fitur:
- Absen masuk
- Absen pulang
- Riwayat absensi
- QR token dinamis

QR token tidak perlu disimpan sebagai ribuan record permanen.

Data absensi:
- pegawai_id
- tanggal
- jam_masuk
- jam_pulang
- status

Jadwal kerja pegawai (`jadwal_pegawai`) menjadi dasar pembanding jam absensi.
Penentuan status hadir/terlambat dan alpa dikerjakan pada fase absensi
(lihat catatan implementasi di bawah).

> Catatan Implementasi Phase 9:
> - **QR token dinamis (stateless):** `app/Services/AbsensiQrService` membuat
>   token `window.signature` (HMAC-SHA256 atas window 10 detik, kunci dari
>   `app.key`) — tidak ada tabel `qr_tokens` dan tidak ada record permanen.
>   `verify()` menerima window saat ini atau window sebelumnya (masa tenggang
>   ˡ). Token dihasilkan di server (admin/kepala) dan QR menautkan ke
>   `petugas.absensi.scan-token/{token}`.
> - **Encoder QR tanpa dependensi:** `app/Support/QrCode` (byte mode, EC L,
>   mask 0, versi 1–14, output SVG). Format & version info diverifikasi sesuai
>   tabel standar; konten lebih besar dari versi 14 ditolak.
> - **Alur scan petugas:** identitas pegawai SELALU dari `auth()->user()->pegawai`
>   (bukan dari request). Scan pertama → `jam_masuk` + status `hadir`/`terlambat`
>   (perbandingan dengan `jam_masuk` jadwal); scan berikutnya → `jam_pulang`.
>   Tanpa jadwal, jadwal `izin`/`libur`, token tidak sah/kadaluwarsa, atau setelah
>   jam pulang → scan ditolak. Semua di dalam transaksi DB.
> - **Unik:** satu `absensi` per `(pegawai_id, tanggal)` (constraint unik baru).
> - **Rekap:** admin & kepala melihat rekap harian dari `jadwal_pegawai`
>   (`terjadwal` tanpa absensi → `alpa`; `izin`, `libur`, dan status absensi
>   `hadir`/`terlambat`; filter status). Petugas melihat "Absensi Saya".
> - Akses: `admin.absensi.*` & `kepala.absensi.*` (index, qr, token, qr-img);
>   `petugas.absensi.*` (index, scan POST, scan-token GET).
> - Scan melalui kamera ponsel: QR membuka URL `petugas.absensi.scan-token`
>   di browser petugas yang sudah login; fallback input token manual.

## 11.1 Jadwal Kerja Pegawai

Jadwal kerja merupakan bagian dari modul kepegawaian dan menjadi acuan
pembanding absensi pegawai:

```
Pegawai
→ Jadwal Kerja
→ Absensi
```

Aturan FINAL MVP:

- Satu pegawai **maksimal memiliki satu jadwal per tanggal** (unique
  `pegawai_id` + `tanggal`).
- **Split shift tidak didukung** pada MVP.
- Status jadwal hanya:
  - `terjadwal`
  - `izin`
  - `libur`
- `jam_masuk` dan `jam_pulang` nullable hanya untuk status `izin`/`libur`.
  Status `terjadwal` menyimpan jam masuk dan jam pulang (mis. 07:00–16:00).
- Status `terlambat` dan `alpa` **BUKAN** status jadwal. Keduanya ditentukan
  dari perbandingan jadwal dengan data absensi pada fase absensi.
  Contoh: jadwal 07:00–16:00, absensi 06:55 → hadir, 07:03 → hadir,
  07:17 → terlambat; jadwal aktif tanpa absensi → alpa.

Data jadwal pegawai:
- id
- pegawai_id
- tanggal
- jam_masuk
- jam_pulang
- status
- catatan (nullable)

## 12. Laporan

MVP membutuhkan:

- Laporan produksi
- Laporan stok
- Laporan distribusi
- Laporan penerimaan
- Laporan absensi

Filter minimal:
- tanggal
- status
- sekolah jika relevan

Export PDF/Excel belum menjadi prioritas MVP.

## 13. Alur Bisnis Utama

### Workflow Produksi & Distribusi (FINAL)

1. Admin/petugas menentukan menu dan tanggal produksi.
2. Buat rencana distribusi berdasarkan sekolah tujuan. Setiap sekolah memiliki
   jumlah porsi yang dibutuhkan.

   ```
   - Sekolah A = 900 porsi
   - Sekolah B = 700 porsi
   - Sekolah C = 400 porsi
   ```

3. Sistem menghitung total kebutuhan: 900 + 700 + 400 = **2.000 porsi**.
4. Sistem membuat/menentukan target produksi berdasarkan total kebutuhan:
   **target produksi = 2.000 porsi**.
5. Petugas melakukan produksi.
6. Setelah produksi selesai, sistem mencatat hasil produksi aktual.

   ```
   Target     = 2.000
   Hasil      = 1.980
   Kekurangan = 20
   ```

7. Jika hasil produksi mencukupi kebutuhan, makanan dapat diproses ke tahap
   pemorsian dan packing.
8. Setelah siap, distribusi dikirim ke masing-masing sekolah sesuai jumlah
   porsi yang telah direncanakan.
9. Sekolah menerima pengiriman.
10. Penerimaan dicatat satu kali untuk setiap distribusi.
11. Data masuk ke laporan.

### Alur kebutuhan produksi

```
Sekolah
→ Rencana Distribusi
→ Jumlah Porsi
→ Total Kebutuhan
→ Target Produksi
→ Produksi Aktual
→ Validasi Kekurangan/Kelebihan
```

### Alur pegawai

```
Pegawai
→ Login
→ Jadwal Kerja
→ Absensi QR
→ Jam Masuk / Jam Pulang
```

## 14. Database MVP

Tabel utama yang direncanakan:

- users
- pegawai
- jadwal_pegawai
- sekolah
- menus
- bahan_baku
- stok_mutations
- produksi
- produksi_bahan
- distribusi
- penerimaan
- absensi

Jangan menambahkan tabel besar baru tanpa alasan yang jelas.

## 15. Relationship Utama

Konsep relasi:

```
menus
  ↓
produksi
  ↓
distribusi
  ↓
sekolah
```

```
produksi
  ↓
produksi_bahan
  ↓
bahan_baku
  ↓
stok_mutations
```

```
distribusi
  ↓
penerimaan
```

```
users
  ↓
pegawai
  ├── jadwal_pegawai
  └── absensi
```

Aturan yang menjamin kekonsistenan:

- Satu menu dapat digunakan untuk beberapa rencana distribusi/sekolah.
- Setiap distribusi memiliki satu sekolah tujuan dan satu `jumlah_porsi`.
- Total `jumlah_porsi` dari distribusi yang terkait dengan produksi menjadi
  kebutuhan produksi.
- `target_porsi` produksi berasal dari total kebutuhan distribusi.
- `hasil_porsi` adalah jumlah produksi aktual; sistem menghitung kekurangan
  atau kelebihan produksi.
- Satu distribusi memiliki satu penerimaan; penerimaan tidak dicatat per siswa.
- Jadwal kerja (`jadwal_pegawai`) menjadi dasar pembanding jam absensi pegawai;
  satu pegawai maksimal satu jadwal per tanggal, split shift tidak termasuk MVP.
- Tidak ada data siswa individual.

## 16. Development Principles

Gunakan prinsip berikut:

- Ikuti konvensi Laravel.
- Gunakan solusi sederhana.
- Jangan over-engineer.
- Utamakan maintainability.
- Jangan membuat fitur yang belum diminta.
- Jangan mengubah file yang tidak berhubungan dengan task.
- Jangan menghapus kode existing tanpa alasan jelas.
- Jalankan test yang relevan setelah perubahan.
- Perbaiki root cause error.
- Jangan menyembunyikan error dengan workaround yang tidak jelas.
- Setiap fase harus dapat diverifikasi sebelum lanjut ke fase berikutnya.
- Jika spesifikasi bertentangan dengan implementasi existing, jangan menebak;
  laporkan konflik tersebut.

## 17. Development Workflow

Project akan dikembangkan bertahap:

- **Phase 0:** Project audit + documentation
- **Phase 1:** Database, migrations, models, relationships
- **Phase 2:** Authentication + role authorization
- **Phase 3:** Layout + dashboard
- **Phase 4:** Master data (Phase 4.1 CRUD Pegawai, Phase 4.2 CRUD Sekolah, Phase 4.3 CRUD Menu, Phase 4.4 CRUD Bahan Baku selesai)
- **Phase 5:** Stok + mutasi stok (selesai)
- **Phase 6:** Produksi (selesai)
- **Phase 7:** Distribusi / pengiriman (selesai)
- **Phase 8:** Penerimaan (selesai)
- **Phase 9:** Absensi QR (selesai)
- **Phase 10:** Laporan
- **Phase 11:** Testing + polishing

Setiap phase harus:
1. dikerjakan sesuai scope
2. dites
3. error diperbaiki
4. dilaporkan
5. baru lanjut ke phase berikutnya