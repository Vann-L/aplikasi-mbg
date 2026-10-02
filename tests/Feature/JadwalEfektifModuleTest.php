<?php

use App\Models\Absensi;
use App\Models\JadwalPegawai;
use App\Models\KalenderOperasional;
use App\Models\Pegawai;
use App\Models\PolaJadwal;
use App\Models\User;
use App\Services\AbsensiQrService;
use App\Services\AbsensiScanException;
use App\Services\AbsensiService;
use App\Services\JadwalEfektifService;
use App\Values\JadwalEfektif;
use Carbon\CarbonImmutable;
use Database\Factories\PolaJadwalFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    CarbonImmutable::setTestNow(null);
});

function efektifAdmin(): User
{
    return User::factory()->admin()->create();
}

function efektifKepala(): User
{
    return User::factory()->kepala()->create();
}

/**
 * @return array{user: User, pegawai: Pegawai}
 */
function efektifPetugas(): array
{
    $user = User::factory()->petugas()->create();

    return [
        'user' => $user,
        'pegawai' => Pegawai::factory()->create([
            'user_id' => $user->id,
            'status' => Pegawai::STATUS_ACTIVE,
        ]),
    ];
}

/**
 * @return array{1: array{status: string, jam_masuk: string, jam_pulang: string}, 2: array{status: string, jam_masuk: string, jam_pulang: string}, 3: array{status: string, jam_masuk: string, jam_pulang: string}, 4: array{status: string, jam_masuk: string, jam_pulang: string}, 5: array{status: string, jam_masuk: string, jam_pulang: string}, 6: array{status: string, jam_masuk: string, jam_pulang: string}, 7: array{status: string, jam_masuk: string, jam_pulang: string}}
 */
function polaHariPayload(array $jam = ['07:00', '16:00']): array
{
    $hari = [];

    for ($i = 1; $i <= 7; $i++) {
        $hari[$i] = [
            'status' => PolaJadwal::STATUS_KERJA,
            'jam_masuk' => $jam[0],
            'jam_pulang' => $jam[1],
        ];
    }

    return $hari;
}

function polaPayload(int $pegawaiId, string $berlakuMulai, array $overrides = []): array
{
    return array_merge([
        'pegawai_id' => $pegawaiId,
        'berlaku_mulai' => $berlakuMulai,
        'keterangan' => null,
        'hari' => polaHariPayload(),
    ], $overrides);
}

function efektifService(): JadwalEfektifService
{
    return app(JadwalEfektifService::class);
}

test('factory pola jadwal menghasilkan pola kerja Senin-Jumat dan libur Sabtu-Minggu', function () {
    $pola = PolaJadwal::factory()->create();

    expect($pola->hari)->toHaveCount(7);
    expect($pola->hari[0]['status'])->toBe(PolaJadwal::STATUS_KERJA);
    expect($pola->hari[4]['status'])->toBe(PolaJadwal::STATUS_KERJA);
    expect($pola->hari[5]['status'])->toBe(PolaJadwal::STATUS_LIBUR);
    expect($pola->hari[6]['status'])->toBe(PolaJadwal::STATUS_LIBUR);
    expect($pola->pegawai)->not->toBeNull();
});

test('hariPada memetakan tanggal ke entri hari ISO pada indeks yang benar', function () {
    $hari = PolaJadwalFactory::polaHari(['07:00', '16:00']);
    $hari[0] = ['status' => PolaJadwal::STATUS_KERJA, 'jam_masuk' => '06:30', 'jam_pulang' => '15:30'];

    $pola = PolaJadwal::factory()->create(['hari' => $hari]);

    $senin = CarbonImmutable::parse('2026-01-05');
    $selasa = $senin->addDay();

    expect($pola->hariPada($senin)['jam_masuk'])->toBe('06:30');
    expect($pola->hariPada($selasa)['jam_masuk'])->toBe('07:00');
    expect($pola->hariPada($senin->addDays(5))['status'])->toBe(PolaJadwal::STATUS_LIBUR);
});

test('efektif tanpa pola maupun override menghasilkan null', function () {
    $pegawai = Pegawai::factory()->create();

    expect(efektifService()->efektif($pegawai, CarbonImmutable::parse('2026-01-05')))->toBeNull();
});

test('efektif lebih mengutamakan override daripada pola', function () {
    $pegawai = Pegawai::factory()->create();
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);
    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '06:00',
        'jam_pulang' => '15:00',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);

    $efektif = efektifService()->efektif($pegawai, CarbonImmutable::parse('2026-01-05'));

    expect($efektif)->toBeInstanceOf(JadwalEfektif::class);
    expect($efektif->sumber)->toBe('override');
    expect($efektif->jamMasuk->format('H:i'))->toBe('06:00');
    expect($efektif->jamPulang->format('H:i'))->toBe('15:00');
});

test('efektif lebih mengutamakan kalender libur daripada override', function () {
    $pegawai = Pegawai::factory()->create();
    KalenderOperasional::factory()->create(['tanggal' => '2026-01-05', 'status' => KalenderOperasional::STATUS_LIBUR]);
    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);

    $efektif = efektifService()->efektif($pegawai, CarbonImmutable::parse('2026-01-05'));

    expect($efektif->sumber)->toBe('kalender');
    expect($efektif->status)->toBe(JadwalPegawai::STATUS_LIBUR);
    expect($efektif->jamMasuk)->toBeNull();
});

test('efektif diturunkan dari pola saat tidak ada override', function () {
    $pegawai = Pegawai::factory()->create();
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);

    $efektif = efektifService()->efektif($pegawai, CarbonImmutable::parse('2026-01-05'));

    expect($efektif->sumber)->toBe('pola');
    expect($efektif->status)->toBe(JadwalPegawai::STATUS_TERJADWAL);
    expect($efektif->jamMasuk->format('H:i'))->toBe('07:00');
    expect($efektif->jamPulang->format('H:i'))->toBe('16:00');
});

test('efektif memakai pola terbaru yang sudah berlaku', function () {
    $pegawai = Pegawai::factory()->create();
    $hariA = PolaJadwalFactory::polaHari(['07:00', '16:00']);
    $hariB = PolaJadwalFactory::polaHari(['09:00', '18:00']);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05', 'hari' => $hariA]);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-08', 'hari' => $hariB]);

    $efektif = efektifService()->efektif($pegawai, CarbonImmutable::parse('2026-01-12'));

    expect($efektif->sumber)->toBe('pola');
    expect($efektif->jamMasuk->format('H:i'))->toBe('09:00');
});

test('efektif mengikuti status libur harian pada pola', function () {
    $pegawai = Pegawai::factory()->create();
    $hari = PolaJadwalFactory::polaHari();
    $hari[5] = ['status' => PolaJadwal::STATUS_LIBUR, 'jam_masuk' => null, 'jam_pulang' => null];
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05', 'hari' => $hari]);

    $efektif = efektifService()->efektif($pegawai, CarbonImmutable::parse('2026-01-10'));

    expect($efektif->status)->toBe(JadwalPegawai::STATUS_LIBUR);
    expect($efektif->jamMasuk)->toBeNull();
});

test('efektif null sebelum pola mulai berlaku', function () {
    $pegawai = Pegawai::factory()->create();
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-12']);

    expect(efektifService()->efektif($pegawai, CarbonImmutable::parse('2026-01-05')))->toBeNull();
});

test('kalenderPada dan isHariLiburOperasional membaca kalender operasional', function () {
    KalenderOperasional::factory()->create(['tanggal' => '2026-01-08', 'status' => KalenderOperasional::STATUS_LIBUR]);

    $service = efektifService();

    expect($service->kalenderPada(CarbonImmutable::parse('2026-01-08'))?->status)->toBe(KalenderOperasional::STATUS_LIBUR);
    expect($service->isHariLiburOperasional(CarbonImmutable::parse('2026-01-08')))->toBeTrue();
    expect($service->kalenderPada(CarbonImmutable::parse('2026-01-07')))->toBeNull();
});

test('statusRekap mengutamakan absensi bila ada', function () {
    $pegawai = Pegawai::factory()->create();
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);
    $absensi = Absensi::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
        'status' => Absensi::STATUS_HADIR,
    ]);

    $efektif = efektifService()->efektif($pegawai, CarbonImmutable::parse('2026-01-05'));

    expect(efektifService()->statusRekap($efektif, $absensi))->toBe(Absensi::STATUS_HADIR);
});

test('statusRekap menurunkan alpa untuk terjadwal tanpa absensi', function () {
    $pegawai = Pegawai::factory()->create();
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);

    $efektif = efektifService()->efektif($pegawai, CarbonImmutable::parse('2026-01-05'));

    expect(efektifService()->statusRekap($efektif, null))->toBe(Absensi::STATUS_ALPA);
    expect(efektifService()->statusRekap(null, null))->toBe('tidak_terjadwal');
});

test('rekapHarian memunculkan alpa untuk seluruh pegawai aktif ber-pola tanpa absensi', function () {
    $pegawaiA = Pegawai::factory()->create(['nama' => 'Bagus', 'status' => Pegawai::STATUS_ACTIVE]);
    $pegawaiB = Pegawai::factory()->create(['nama' => 'Andi', 'status' => Pegawai::STATUS_ACTIVE]);
    Pegawai::factory()->create(['status' => Pegawai::STATUS_INACTIVE]);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawaiA->id, 'berlaku_mulai' => '2026-01-05']);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawaiB->id, 'berlaku_mulai' => '2026-01-05']);

    $rows = efektifService()->rekapHarian(CarbonImmutable::parse('2026-01-05'));

    expect($rows)->toHaveCount(2);
    expect($rows->pluck('pegawai.nama')->all())->toBe(['Andi', 'Bagus']);
    expect($rows->every(fn (array $row): bool => $row['status'] === Absensi::STATUS_ALPA))->toBeTrue();
});

test('rekapHarian mencatat izin dari override', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
        'status' => JadwalPegawai::STATUS_IZIN,
    ]);

    $rows = efektifService()->rekapHarian(CarbonImmutable::parse('2026-01-05'));

    expect($rows->first()['status'])->toBe(Absensi::STATUS_IZIN);
});

test('pemindaian mengikuti jam masuk dari pola', function () {
    $pegawai = Pegawai::factory()->create();
    $now = CarbonImmutable::parse('2026-01-08 07:00:00', config('app.timezone'));
    CarbonImmutable::setTestNow($now);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);

    $qr = new AbsensiQrService;
    $result = app(AbsensiService::class)->scan($pegawai, $qr->generate($now), $now);

    expect($result['status'])->toBe(Absensi::STATUS_HADIR);

    $absensi = $pegawai->absensiPada($now);

    expect($absensi)->not->toBeNull();
    expect($absensi->tanggal->toDateString())->toBe('2026-01-08');
});

test('pemindaian ditolak pada hari libur kalender operasional', function () {
    $pegawai = Pegawai::factory()->create();
    $now = CarbonImmutable::parse('2026-01-08 07:00:00', config('app.timezone'));
    CarbonImmutable::setTestNow($now);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);
    KalenderOperasional::factory()->create(['tanggal' => '2026-01-08', 'status' => KalenderOperasional::STATUS_LIBUR]);

    $qr = new AbsensiQrService;

    expect(fn () => app(AbsensiService::class)->scan($pegawai, $qr->generate($now), $now))
        ->toThrow(AbsensiScanException::class, 'libur');

    expect($pegawai->absensiPada($now))->toBeNull();
});

test('monitoringMingguan dimulai dari hari Senin', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);

    $rows = efektifService()->monitoringMingguan(CarbonImmutable::parse('2026-01-08'));

    $baris = $rows->first();
    $tanggalList = $baris['hari']->pluck('tanggal');

    expect($tanggalList->first()->isMonday())->toBeTrue();
    expect($tanggalList->first()->toDateString())->toBe('2026-01-05');
    expect($tanggalList->last()->toDateString())->toBe('2026-01-11');
});

test('monitoring menandai alpa pada hari lewat dan belum absen pada hari ini atau mendatang', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $now = CarbonImmutable::parse('2026-01-08 07:00:00', config('app.timezone'));
    CarbonImmutable::setTestNow($now);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);

    $statuses = efektifService()->monitoringMingguan($now)->first()['hari']->pluck('status');

    expect($statuses->slice(0, 3)->all())->toBe([Absensi::STATUS_ALPA, Absensi::STATUS_ALPA, Absensi::STATUS_ALPA]);
    expect($statuses->get(3))->toBe('belum');
    expect($statuses->get(4))->toBe('belum');
    expect($statuses->get(5))->toBe(JadwalPegawai::STATUS_LIBUR);
    expect($statuses->get(6))->toBe(JadwalPegawai::STATUS_LIBUR);
});

test('monitoring menandai libur harian dari pola', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $now = CarbonImmutable::parse('2026-01-07 07:00:00', config('app.timezone'));
    CarbonImmutable::setTestNow($now);
    $hari = PolaJadwalFactory::polaHari();
    $hari[2] = ['status' => PolaJadwal::STATUS_LIBUR, 'jam_masuk' => null, 'jam_pulang' => null];
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05', 'hari' => $hari]);

    $statuses = efektifService()->monitoringMingguan($now)->first()['hari']->pluck('status');

    expect($statuses->get(2))->toBe(JadwalPegawai::STATUS_LIBUR);
});

test('monitoring mencerminkan absensi tercatat', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $now = CarbonImmutable::parse('2026-01-08 07:00:00', config('app.timezone'));
    CarbonImmutable::setTestNow($now);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);
    Absensi::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-08',
        'jam_masuk' => '2026-01-08 07:05:00',
        'jam_pulang' => null,
        'status' => Absensi::STATUS_HADIR,
    ]);

    $statuses = efektifService()->monitoringMingguan($now)->first()['hari']->pluck('status');

    expect($statuses->get(3))->toBe(Absensi::STATUS_HADIR);
});

test('admin dapat menyimpan pola dan melihatnya pada matriks', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $admin = efektifAdmin();

    $this->actingAs($admin)
        ->post(route('admin.jadwal.pola.store'), polaPayload($pegawai->id, '2026-01-05', ['keterangan' => 'Pola tetap']))
        ->assertRedirect(route('admin.jadwal.pola'));

    $this->assertDatabaseHas('pola_jadwal', ['pegawai_id' => $pegawai->id, 'keterangan' => 'Pola tetap']);

    $this->actingAs($admin)
        ->get(route('admin.jadwal.pola'))
        ->assertOk()
        ->assertSee($pegawai->nama)
        ->assertSee('Senin');
});

test('validasi pola menolak hari kerja tanpa jam masuk', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);

    $payload = polaPayload($pegawai->id, '2026-01-05');
    $payload['hari'][1] = ['status' => PolaJadwal::STATUS_KERJA, 'jam_masuk' => null, 'jam_pulang' => null];

    $this->actingAs(efektifAdmin())
        ->post(route('admin.jadwal.pola.store'), $payload)
        ->assertSessionHasErrors(['hari.0.jam_masuk' => 'Jam masuk wajib diisi untuk hari kerja (Senin).']);

    $this->assertDatabaseCount('pola_jadwal', 0);
});

test('validasi pola menolak tanggal mulai yang sudah dipakai pegawai', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);

    $this->actingAs(efektifAdmin())
        ->post(route('admin.jadwal.pola.store'), polaPayload($pegawai->id, '2026-01-05'))
        ->assertSessionHasErrors('berlaku_mulai');
});

test('update pola mengizinkan tanggal mulai yang sama', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $pola = PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);

    $this->actingAs(efektifAdmin())
        ->put(route('admin.jadwal.pola.update', $pola), polaPayload($pegawai->id, '2026-01-05', ['keterangan' => 'Direvisi']))
        ->assertRedirect(route('admin.jadwal.pola'));

    expect($pola->fresh()->keterangan)->toBe('Direvisi');
});

test('pola dengan absensi pada periode yang dicakup tidak dapat dihapus', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $pola = PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);
    Absensi::factory()->create(['pegawai_id' => $pegawai->id, 'tanggal' => '2026-01-07']);

    $this->actingAs(efektifAdmin())
        ->delete(route('admin.jadwal.pola.destroy', $pola))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('pola_jadwal', ['id' => $pola->id]);
});

test('pola tanpa absensi dapat dihapus admin', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $pola = PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);

    $this->actingAs(efektifAdmin())
        ->delete(route('admin.jadwal.pola.destroy', $pola))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('pola_jadwal', ['id' => $pola->id]);
});

test('kepala dapat melihat matriks pola secara read-only', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);

    $this->actingAs(efektifKepala())
        ->get(route('kepala.jadwal.pola'))
        ->assertOk()
        ->assertSee($pegawai->nama);
});

test('petugas dilarang mengakses halaman pola admin', function () {
    $petugas = efektifPetugas();

    $this->actingAs($petugas['user'])
        ->get(route('admin.jadwal.pola'))
        ->assertForbidden();
});

test('admin dapat mengelola kalender operasional', function () {
    $admin = efektifAdmin();

    $this->actingAs($admin)
        ->post(route('admin.jadwal.kalender.store'), [
            'tanggal' => '2026-02-02',
            'status' => KalenderOperasional::STATUS_LIBUR,
            'alasan' => 'Hari libur nasional demo',
            'catatan' => null,
        ])
        ->assertSessionHas('status');

    $kalender = KalenderOperasional::first();

    expect($kalender->tanggal->toDateString())->toBe('2026-02-02');
    expect($kalender->status)->toBe(KalenderOperasional::STATUS_LIBUR);

    $this->actingAs($admin)
        ->put(route('admin.jadwal.kalender.update', $kalender), ['status' => KalenderOperasional::STATUS_BUKA])
        ->assertSessionHas('status');

    expect($kalender->fresh()->status)->toBe(KalenderOperasional::STATUS_BUKA);

    $this->actingAs($admin)
        ->delete(route('admin.jadwal.kalender.destroy', $kalender))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('kalender_operasional', ['id' => $kalender->id]);
});

test('kalender operasional menolak tanggal ganda', function () {
    KalenderOperasional::factory()->create(['tanggal' => '2026-02-02', 'status' => KalenderOperasional::STATUS_LIBUR]);

    $this->actingAs(efektifAdmin())
        ->post(route('admin.jadwal.kalender.store'), [
            'tanggal' => '2026-02-02',
            'status' => KalenderOperasional::STATUS_BUKA,
        ])
        ->assertSessionHas('error');

    $this->assertDatabaseCount('kalender_operasional', 1);
});

test('kepala dapat melihat kalender operasional secara read-only', function () {
    $kalender = KalenderOperasional::factory()->create(['tanggal' => '2026-02-02', 'status' => KalenderOperasional::STATUS_LIBUR, 'alasan' => 'Cuti bersama']);

    $this->actingAs(efektifKepala())
        ->get(route('kepala.jadwal.kalender'))
        ->assertOk()
        ->assertSee($kalender->alasan);
});

test('halaman monitoring menampilkan matriks absensi mingguan', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $now = CarbonImmutable::parse('2026-01-08 07:00:00', config('app.timezone'));
    CarbonImmutable::setTestNow($now);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);
    Absensi::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-08',
        'status' => Absensi::STATUS_HADIR,
    ]);

    $this->actingAs(efektifAdmin())
        ->get(route('admin.jadwal.monitoring', ['tanggal' => '2026-01-08']))
        ->assertOk()
        ->assertSee($pegawai->nama)
        ->assertSee('hadir');
});

test('kepala dapat melihat monitoring mingguan', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);

    $this->actingAs(efektifKepala())
        ->get(route('kepala.jadwal.monitoring'))
        ->assertOk()
        ->assertSee($pegawai->nama);
});

test('admin dapat menukar jadwal dua pegawai pada satu tanggal', function () {
    $pegawaiA = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $pegawaiB = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawaiA->id, 'berlaku_mulai' => '2026-01-05', 'hari' => PolaJadwalFactory::polaHari(['07:00', '16:00'])]);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawaiB->id, 'berlaku_mulai' => '2026-01-05', 'hari' => PolaJadwalFactory::polaHari(['08:00', '17:00'])]);

    $this->actingAs(efektifAdmin())
        ->post(route('admin.jadwal.tukar'), [
            'tanggal' => '2026-01-05',
            'pegawai_a' => $pegawaiA->id,
            'pegawai_b' => $pegawaiB->id,
        ])
        ->assertRedirect(route('admin.jadwal.index'));

    $overrideA = JadwalPegawai::where('pegawai_id', $pegawaiA->id)->whereDate('tanggal', '2026-01-05')->first();
    $overrideB = JadwalPegawai::where('pegawai_id', $pegawaiB->id)->whereDate('tanggal', '2026-01-05')->first();

    expect($overrideA->jam_masuk->format('H:i'))->toBe('08:00');
    expect($overrideA->jam_pulang->format('H:i'))->toBe('17:00');
    expect($overrideA->status)->toBe(JadwalPegawai::STATUS_TERJADWAL);
    expect($overrideB->jam_masuk->format('H:i'))->toBe('07:00');
    expect($overrideB->jam_pulang->format('H:i'))->toBe('16:00');
});

test('tukar ditolak bila salah satu pegawai tidak memiliki jadwal efektif', function () {
    $pegawaiA = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $pegawaiB = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawaiA->id, 'berlaku_mulai' => '2026-01-05']);

    $this->actingAs(efektifAdmin())
        ->post(route('admin.jadwal.tukar'), [
            'tanggal' => '2026-01-05',
            'pegawai_a' => $pegawaiA->id,
            'pegawai_b' => $pegawaiB->id,
        ])
        ->assertSessionHas('error');

    $this->assertDatabaseCount('jadwal_pegawai', 0);
});

test('laporan absensi menurunkan status dari pola yang berlaku', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    PolaJadwal::factory()->create(['pegawai_id' => $pegawai->id, 'berlaku_mulai' => '2026-01-05']);

    $this->actingAs(efektifAdmin())
        ->get(route('admin.laporan.absensi', ['tanggal_awal' => '2026-01-05', 'tanggal_akhir' => '2026-01-05']))
        ->assertOk()
        ->assertSee($pegawai->nama)
        ->assertSee('05/01/2026')
        ->assertSee('Alpa');
});

test('petugas melihat jadwal efektif saya dari pola mingguan', function () {
    $petugas = efektifPetugas();
    $mulai = CarbonImmutable::today(config('app.timezone'))->subDays(14);
    PolaJadwal::factory()->create(['pegawai_id' => $petugas['pegawai']->id, 'berlaku_mulai' => $mulai->toDateString()]);

    $this->actingAs($petugas['user'])
        ->get(route('petugas.jadwal.index'))
        ->assertOk()
        ->assertSee($petugas['pegawai']->nama)
        ->assertSee('07:00 - 16:00');
});
