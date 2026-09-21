<?php

namespace Database\Seeders;

use App\Models\Absensi;
use App\Models\BahanBaku;
use App\Models\Distribusi;
use App\Models\JadwalPegawai;
use App\Models\Menu;
use App\Models\Pegawai;
use App\Models\Penerimaan;
use App\Models\Produksi;
use App\Models\ProduksiBahan;
use App\Models\Sekolah;
use App\Models\StokMutation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Seed realistic demo data so every SPPG feature can be inspected manually.
 *
 * Safety: refuses to run in production, only creates/repairs demo records
 * (identified by the [DEMO] marker), and is idempotent so it can be re-run.
 */
class DemoDataSeeder extends Seeder
{
    private const DEMO = '[DEMO]';

    /**
     * "Today" at the start of the operational day in the application timezone
     * (single source of truth: config('app.timezone')), not the PHP/OS default.
     */
    private function hariIni(): CarbonImmutable
    {
        return CarbonImmutable::today(config('app.timezone'));
    }

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoDataSeeder hanya boleh dijalankan di lingkungan local/development, bukan production.');
        }

        $admin = $this->seedUsers();

        $petugasProduksi = $this->seedPegawai($admin);
        $this->seedSekolah();
        $this->seedMenu();
        $bahan = $this->seedBahanBaku();

        $this->hapusDataDemo();

        $this->seedStokMasuk($bahan, $admin);

        $produksi1 = $this->seedProduksi1($bahan, $admin);
        $produksi2 = $this->seedProduksi2($bahan, $admin);

        $distribusi = $this->seedDistribusi($produksi1, $produksi2, $petugasProduksi, $admin);

        $this->seedPenerimaan($distribusi, $admin);

        $this->seedJadwalAbsensi($petugasProduksi);
    }

    /**
     * Create (or restore) the three demo user accounts.
     */
    private function seedUsers(): User
    {
        $password = Hash::make('password');

        $admin = User::updateOrCreate(['email' => 'admin@mbg.test'], [
            'name' => 'Administrator SPPG',
            'password' => $password,
            'role' => 'admin',
            'status' => 'active',
        ]);

        User::updateOrCreate(['email' => 'kepala@mbg.test'], [
            'name' => 'Kepala SPPG',
            'password' => $password,
            'role' => 'kepala',
            'status' => 'active',
        ]);

        User::updateOrCreate(['email' => 'petugas@mbg.test'], [
            'name' => 'Petugas Produksi',
            'password' => $password,
            'role' => 'petugas',
            'status' => 'active',
        ]);

        return $admin;
    }

    /**
     * Create the five demo employees and link the demo accounts to them.
     */
    private function seedPegawai(User $admin): Pegawai
    {
        $kepala = User::where('email', 'kepala@mbg.test')->firstOrFail();
        $petugas = User::where('email', 'petugas@mbg.test')->firstOrFail();

        $pegawai = [
            ['id_pegawai' => 'DEMO-K001', 'user_id' => $kepala->id, 'nama' => 'Ahmad Sulaiman', 'jabatan' => 'Kepala SPPG', 'bagian' => 'Manajemen', 'no_hp' => '0812-3456-7801'],
            ['id_pegawai' => 'DEMO-K002', 'user_id' => null, 'nama' => 'Rina Marlina', 'jabatan' => 'Chef', 'bagian' => 'Dapur', 'no_hp' => '0812-3456-7802'],
            ['id_pegawai' => 'DEMO-K003', 'user_id' => $petugas->id, 'nama' => 'Budi Santoso', 'jabatan' => 'Petugas Produksi', 'bagian' => 'Produksi', 'no_hp' => '0812-3456-7803'],
            ['id_pegawai' => 'DEMO-K004', 'user_id' => null, 'nama' => 'Dewi Lestari', 'jabatan' => 'Petugas Distribusi', 'bagian' => 'Distribusi', 'no_hp' => '0812-3456-7804'],
            ['id_pegawai' => 'DEMO-K005', 'user_id' => null, 'nama' => 'Agus Wijaya', 'jabatan' => 'Petugas Packing', 'bagian' => 'Packing', 'no_hp' => '0812-3456-7805'],
        ];

        foreach ($pegawai as $row) {
            Pegawai::updateOrCreate(['id_pegawai' => $row['id_pegawai']], [
                'user_id' => $row['user_id'],
                'nama' => $row['nama'],
                'jabatan' => $row['jabatan'],
                'bagian' => $row['bagian'],
                'no_hp' => $row['no_hp'],
                'status' => Pegawai::STATUS_ACTIVE,
            ]);
        }

        return Pegawai::where('id_pegawai', 'DEMO-K003')->firstOrFail();
    }

    /**
     * Create the four demo schools.
     */
    private function seedSekolah(): void
    {
        $sekolah = [
            ['npsn' => '2023400001', 'nama' => 'SMK Negeri 1 Bandung', 'alamat' => 'Jl. Ciliwung No. 4, Bandung', 'kontak' => '022-7036001', 'jumlah_penerima' => 900],
            ['npsn' => '2023400002', 'nama' => 'SMP Negeri 5 Bandung', 'alamat' => 'Jl. Sumatra No. 12, Bandung', 'kontak' => '022-7036002', 'jumlah_penerima' => 700],
            ['npsn' => '2023400003', 'nama' => 'SMK Negeri 2 Bandung', 'alamat' => 'Jl. Ciateul No. 8, Bandung', 'kontak' => '022-7036003', 'jumlah_penerima' => 700],
            ['npsn' => '2023400004', 'nama' => 'SMP Negeri 10 Bandung', 'alamat' => 'Jl. Riau No. 15, Bandung', 'kontak' => '022-7036004', 'jumlah_penerima' => 500],
        ];

        foreach ($sekolah as $row) {
            Sekolah::updateOrCreate(['npsn' => $row['npsn']], [
                'nama' => $row['nama'],
                'alamat' => $row['alamat'],
                'kontak' => $row['kontak'],
                'jumlah_penerima' => $row['jumlah_penerima'],
                'status' => Sekolah::STATUS_ACTIVE,
            ]);
        }
    }

    /**
     * Create the three demo menus.
     */
    private function seedMenu(): void
    {
        $menu = [
            ['nama' => 'Nasi Ayam', 'deskripsi' => 'Nasi, ayam goreng, sayur wortel, dan pisang.'],
            ['nama' => 'Nasi Ikan', 'deskripsi' => 'Nasi, ikan goreng, sayur wortel, dan pisang.'],
            ['nama' => 'Nasi Telur', 'deskripsi' => 'Nasi, telur, sayur, dan buah pisang.'],
        ];

        foreach ($menu as $row) {
            Menu::updateOrCreate(['nama' => $row['nama']], [
                'deskripsi' => $row['deskripsi'],
                'foto' => null,
                'status' => Menu::STATUS_ACTIVE,
            ]);
        }
    }

    /**
     * Create the seven demo raw materials.
     *
     * @return array<string, BahanBaku> keyed by short name
     */
    private function seedBahanBaku(): array
    {
        $rows = [
            'Beras' => ['nama' => 'Beras', 'satuan' => 'kg', 'stok_minimum' => 150],
            'Ayam' => ['nama' => 'Ayam', 'satuan' => 'kg', 'stok_minimum' => 120],
            'Ikan' => ['nama' => 'Ikan', 'satuan' => 'kg', 'stok_minimum' => 60],
            'Telur' => ['nama' => 'Telur', 'satuan' => 'pcs', 'stok_minimum' => 150],
            'Minyak' => ['nama' => 'Minyak Goreng', 'satuan' => 'liter', 'stok_minimum' => 20],
            'Wortel' => ['nama' => 'Wortel', 'satuan' => 'kg', 'stok_minimum' => 50],
            'Pisang' => ['nama' => 'Pisang', 'satuan' => 'pcs', 'stok_minimum' => 100],
        ];

        $bahan = [];

        foreach ($rows as $key => $row) {
            $bahan[$key] = BahanBaku::updateOrCreate(['nama' => $row['nama']], [
                'satuan' => $row['satuan'],
                'stok_minimum' => $row['stok_minimum'],
                'status' => BahanBaku::STATUS_ACTIVE,
            ]);
        }

        return $bahan;
    }

    /**
     * Remove every previously seeded demo record so re-running stays idempotent.
     * Only [DEMO]-marked records are touched; real data is never deleted.
     */
    private function hapusDataDemo(): void
    {
        DB::table('stok_mutations')->where('keterangan', 'like', self::DEMO.'%')->delete();
        DB::table('penerimaan')->where('catatan', 'like', self::DEMO.'%')->delete();
        DB::table('distribusi')->where('catatan', 'like', self::DEMO.'%')->delete();

        $produksiIds = DB::table('produksi')->where('catatan', 'like', self::DEMO.'%')->pluck('id');
        DB::table('produksi_bahan')->whereIn('produksi_id', $produksiIds)->delete();
        DB::table('produksi')->whereIn('id', $produksiIds)->delete();
    }

    /**
     * Seed incoming stock mutations for every demo material.
     *
     * @param  array<string, BahanBaku>  $bahan
     */
    private function seedStokMasuk(array $bahan, User $admin): void
    {
        $masuk = [
            'Beras' => 200,
            'Ayam' => 150,
            'Ikan' => 100,
            'Telur' => 500,
            'Minyak' => 50,
            'Wortel' => 80,
            'Pisang' => 500,
        ];

        foreach ($masuk as $key => $jumlah) {
            StokMutation::create([
                'bahan_baku_id' => $bahan[$key]->id,
                'tipe' => StokMutation::TIPE_MASUK,
                'jumlah' => $jumlah,
                'tanggal' => $this->hariIni()->subDays(2),
                'keterangan' => self::DEMO.' Persediaan awal bahan',
                'user_id' => $admin->id,
            ]);
        }
    }

    /**
     * Create the finished production run from yesterday (Nasi Ayam).
     *
     * @param  array<string, BahanBaku>  $bahan
     */
    private function seedProduksi1(array $bahan, User $admin): Produksi
    {
        $kemarin = $this->hariIni()->subDay();
        $menu = Menu::where('nama', 'Nasi Ayam')->firstOrFail();

        $produksi = Produksi::create([
            'menu_id' => $menu->id,
            'tanggal' => $kemarin,
            'target_porsi' => 2000,
            'hasil_porsi' => 2010,
            'status' => Produksi::STATUS_SELESAI,
            'catatan' => self::DEMO.' Produksi demo Nasi Ayam (selesai, kelebihan 10 porsi).',
            'started_at' => $kemarin->setTime(5, 0),
            'completed_at' => $kemarin->setTime(9, 30),
            'stock_deducted_at' => $kemarin->setTime(5, 0),
            'created_by' => $admin->id,
        ]);

        $this->tambahBahanProduksi($produksi, $bahan, $admin, [
            'Beras' => 120,
            'Ayam' => 150,
            'Wortel' => 40,
            'Minyak' => 15,
        ]);

        return $produksi;
    }

    /**
     * Create the still-running production run from today (Nasi Ikan).
     *
     * @param  array<string, BahanBaku>  $bahan
     */
    private function seedProduksi2(array $bahan, User $admin): Produksi
    {
        $today = $this->hariIni();
        $menu = Menu::where('nama', 'Nasi Ikan')->firstOrFail();

        $produksi = Produksi::create([
            'menu_id' => $menu->id,
            'tanggal' => $today,
            'target_porsi' => 1200,
            'hasil_porsi' => 1200,
            'status' => Produksi::STATUS_PEMORSIAN,
            'catatan' => self::DEMO.' Produksi demo Nasi Ikan (sedang berjalan).',
            'started_at' => $today->setTime(5, 0),
            'completed_at' => null,
            'stock_deducted_at' => $today->setTime(5, 0),
            'created_by' => $admin->id,
        ]);

        $this->tambahBahanProduksi($produksi, $bahan, $admin, [
            'Beras' => 75,
            'Ikan' => 80,
            'Wortel' => 30,
            'Minyak' => 10,
        ]);

        return $produksi;
    }

    /**
     * Record the ingredients used by a production and deduct the stock.
     *
     * @param  array<string, BahanBaku>  $bahan
     * @param  array<string, float|int>  $pakai
     */
    private function tambahBahanProduksi(Produksi $produksi, array $bahan, User $admin, array $pakai): void
    {
        foreach ($pakai as $key => $jumlah) {
            $item = $bahan[$key];

            ProduksiBahan::create([
                'produksi_id' => $produksi->id,
                'bahan_baku_id' => $item->id,
                'jumlah' => $jumlah,
                'satuan' => $item->satuan,
            ]);

            StokMutation::create([
                'bahan_baku_id' => $item->id,
                'tipe' => StokMutation::TIPE_KELUAR,
                'jumlah' => $jumlah,
                'tanggal' => $produksi->tanggal,
                'keterangan' => self::DEMO.' Pengurangan stok '.$item->nama.' untuk produksi',
                'user_id' => $admin->id,
            ]);
        }
    }

    /**
     * Create the five demo distributions across both production runs.
     *
     * @return array<string, Distribusi> keyed by demo code
     */
    private function seedDistribusi(Produksi $produksi1, Produksi $produksi2, Pegawai $petugasProduksi, User $admin): array
    {
        $petugasDistribusi = Pegawai::where('id_pegawai', 'DEMO-K004')->firstOrFail();
        $smkn1 = Sekolah::where('npsn', '2023400001')->firstOrFail();
        $smpn5 = Sekolah::where('npsn', '2023400002')->firstOrFail();
        $smkn2 = Sekolah::where('npsn', '2023400003')->firstOrFail();
        $smpn10 = Sekolah::where('npsn', '2023400004')->firstOrFail();

        $kemarin = $this->hariIni()->subDay();

        $rencana = [
            'DEMO-D001' => ['produksi' => $produksi1, 'sekolah' => $smkn1, 'jumlah_porsi' => 900, 'status' => Distribusi::STATUS_SELESAI, 'petugas' => $petugasDistribusi, 'kendaraan' => 'Mobil Box', 'jam' => $kemarin->setTime(9, 0), 'catatan' => self::DEMO.' Distribusi demo Nasi Ayam ke SMK Negeri 1 Bandung.'],
            'DEMO-D002' => ['produksi' => $produksi1, 'sekolah' => $smpn5, 'jumlah_porsi' => 700, 'status' => Distribusi::STATUS_DITERIMA, 'petugas' => $petugasDistribusi, 'kendaraan' => 'Mobil Box', 'jam' => $kemarin->setTime(9, 0), 'catatan' => self::DEMO.' Distribusi demo Nasi Ayam ke SMP Negeri 5 Bandung.'],
            'DEMO-D003' => ['produksi' => $produksi1, 'sekolah' => $smkn2, 'jumlah_porsi' => 400, 'status' => Distribusi::STATUS_DIKIRIM, 'petugas' => $petugasProduksi, 'kendaraan' => 'Pickup', 'jam' => $kemarin->setTime(9, 15), 'catatan' => self::DEMO.' Distribusi demo Nasi Ayam ke SMK Negeri 2 Bandung.'],
            'DEMO-D004' => ['produksi' => $produksi2, 'sekolah' => $smpn10, 'jumlah_porsi' => 500, 'status' => Distribusi::STATUS_DIJADWALKAN, 'petugas' => $petugasDistribusi, 'kendaraan' => 'Pickup', 'jam' => null, 'catatan' => self::DEMO.' Distribusi demo Nasi Ikan ke SMP Negeri 10 Bandung.'],
            'DEMO-D005' => ['produksi' => $produksi2, 'sekolah' => $smkn2, 'jumlah_porsi' => 700, 'status' => Distribusi::STATUS_DISIAPKAN, 'petugas' => $petugasDistribusi, 'kendaraan' => 'Mobil Box', 'jam' => null, 'catatan' => self::DEMO.' Distribusi demo Nasi Ikan ke SMK Negeri 2 Bandung.'],
        ];

        $distribusi = [];

        foreach ($rencana as $kode => $row) {
            $distribusi[$kode] = Distribusi::updateOrCreate(['kode_distribusi' => $kode], [
                'produksi_id' => $row['produksi']->id,
                'sekolah_id' => $row['sekolah']->id,
                'tanggal' => $row['produksi']->tanggal,
                'jumlah_porsi' => $row['jumlah_porsi'],
                'petugas_id' => $row['petugas']->id,
                'kendaraan' => $row['kendaraan'],
                'jam_berangkat' => $row['jam'],
                'status' => $row['status'],
                'catatan' => $row['catatan'],
                'created_by' => $admin->id,
            ]);
        }

        return $distribusi;
    }

    /**
     * Record reception evidence for the finished/received demo distributions.
     *
     * @param  array<string, Distribusi>  $distribusi
     */
    private function seedPenerimaan(array $distribusi, User $admin): void
    {
        $kemarin = $this->hariIni()->subDay();

        Penerimaan::updateOrCreate(
            ['distribusi_id' => $distribusi['DEMO-D001']->id],
            [
                'jumlah_diterima' => 900,
                'waktu_diterima' => $kemarin->setTime(11, 30),
                'penerima_nama' => 'Waka Kesiswaan SMK Negeri 1 Bandung',
                'foto_bukti' => null,
                'catatan' => self::DEMO.' Seluruh porsi diterima sesuai rencana.',
                'created_by' => $admin->id,
            ],
        );

        Penerimaan::updateOrCreate(
            ['distribusi_id' => $distribusi['DEMO-D002']->id],
            [
                'jumlah_diterima' => 690,
                'waktu_diterima' => $kemarin->setTime(11, 45),
                'penerima_nama' => 'Kepala SMP Negeri 5 Bandung',
                'foto_bukti' => null,
                'catatan' => self::DEMO.' Diterima 690 dari 700 porsi, selisih 10 porsi.',
                'created_by' => $admin->id,
            ],
        );
    }

    /**
     * Create demo schedules and attendance for the past few days.
     *
     * Historical days cover every recap status (hadir, terlambat, izin, alpa).
     * Today, the demo petugas account (DEMO-K003 -> petugas@mbg.test) is left
     * without any attendance record so a fresh QR scan can always be performed.
     */
    private function seedJadwalAbsensi(Pegawai $petugasProduksi): void
    {
        $today = $this->hariIni();
        $kemarin = $today->subDay();
        $tigaHariLalu = $today->subDays(3);

        $schedule = [
            'DEMO-K003' => ['jam_masuk' => '07:00', 'jam_pulang' => '16:00'],
            'DEMO-K004' => ['jam_masuk' => '07:00', 'jam_pulang' => '16:00'],
            'DEMO-K005' => ['jam_masuk' => '08:00', 'jam_pulang' => '17:00'],
        ];

        $pegawai = Pegawai::whereIn('id_pegawai', array_keys($schedule))->get()->keyBy('id_pegawai');

        foreach ($schedule as $kode => $jam) {
            $model = $pegawai[$kode];

            foreach ([$kemarin, $today] as $tanggal) {
                $jamMasuk = $tanggal->eq($today) && $model->is($petugasProduksi)
                    ? CarbonImmutable::now(config('app.timezone'))->addMinutes(10)
                    : $jam['jam_masuk'];

                JadwalPegawai::updateOrCreate(
                    ['pegawai_id' => $model->id, 'tanggal' => $tanggal],
                    [
                        'jam_masuk' => $jamMasuk,
                        'jam_pulang' => $jam['jam_pulang'],
                        'status' => JadwalPegawai::STATUS_TERJADWAL,
                        'catatan' => null,
                    ],
                );
            }
        }

        $petugasDistribusi = $pegawai['DEMO-K004'];

        JadwalPegawai::updateOrCreate(
            ['pegawai_id' => $petugasDistribusi->id, 'tanggal' => $tigaHariLalu],
            [
                'jam_masuk' => null,
                'jam_pulang' => null,
                'status' => JadwalPegawai::STATUS_IZIN,
                'catatan' => self::DEMO.' Izin urusan keluarga.',
            ],
        );

        // The demo petugas account stays scannable today: any attendance record
        // that would block a fresh QR scan is removed on every reseed. Only the
        // demo petugas is touched, never other employees.
        Absensi::query()
            ->where('pegawai_id', $petugasProduksi->id)
            ->whereDate('tanggal', $today)
            ->delete();

        // Historical recap examples (yesterday): hadir for the demo petugas,
        // terlambat for the distribution staff; the packing staff keeps alpa.
        Absensi::updateOrCreate(
            ['pegawai_id' => $petugasProduksi->id, 'tanggal' => $kemarin],
            [
                'jam_masuk' => $kemarin->format('Y-m-d').' 06:55:00',
                'jam_pulang' => $kemarin->format('Y-m-d').' 16:05:00',
                'status' => Absensi::STATUS_HADIR,
            ],
        );

        Absensi::updateOrCreate(
            ['pegawai_id' => $petugasDistribusi->id, 'tanggal' => $kemarin],
            [
                'jam_masuk' => $kemarin->format('Y-m-d').' 07:25:00',
                'jam_pulang' => null,
                'status' => Absensi::STATUS_TERLAMBAT,
            ],
        );

        Absensi::updateOrCreate(
            ['pegawai_id' => $petugasDistribusi->id, 'tanggal' => $today],
            [
                'jam_masuk' => $today->format('Y-m-d').' 07:25:00',
                'jam_pulang' => null,
                'status' => Absensi::STATUS_TERLAMBAT,
            ],
        );
    }
}
