<?php

use App\Models\Absensi;
use App\Models\JadwalPegawai;
use App\Models\KalenderOperasional;
use App\Models\Pegawai;
use App\Models\PolaJadwal;
use App\Models\User;
use App\Services\JadwalEfektifService;
use App\Values\JadwalEfektif;
use Carbon\CarbonImmutable;
use Database\Factories\PolaJadwalFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    CarbonImmutable::setTestNow(null);
});

function konsistenAdmin(): User
{
    return User::factory()->admin()->create();
}

/**
 * @return array{0: User, 1: Pegawai}
 */
function konsistenPetugas(): array
{
    $user = User::factory()->petugas()->create();

    return [
        $user,
        Pegawai::factory()->create([
            'user_id' => $user->id,
            'status' => Pegawai::STATUS_ACTIVE,
        ]),
    ];
}

test('dashboard menampilkan jadwal efektif dari pola', function () {
    CarbonImmutable::setTestNow('2026-01-08 07:00:00', config('app.timezone'));
    [$user, $pegawai] = konsistenPetugas();
    PolaJadwal::factory()->create([
        'pegawai_id' => $pegawai->id,
        'berlaku_mulai' => '2026-01-05',
        'hari' => PolaJadwalFactory::polaHari(['07:00', '16:00']),
    ]);

    $this->actingAs($user)->get(route('petugas.dashboard'))
        ->assertOk()
        ->assertViewHas('jadwalEfektifHariIni', fn (JadwalEfektif $jadwal): bool => $jadwal->sumber === 'pola'
            && $jadwal->status === JadwalPegawai::STATUS_TERJADWAL
            && $jadwal->jamMasuk?->format('H:i') === '07:00')
        ->assertSee('07:00');
});

test('dashboard tidak salah membaca override saja saat kalender libur menang', function () {
    CarbonImmutable::setTestNow('2026-01-08 07:00:00', config('app.timezone'));
    [$user, $pegawai] = konsistenPetugas();
    KalenderOperasional::factory()->create(['tanggal' => '2026-01-08', 'status' => KalenderOperasional::STATUS_LIBUR]);
    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-08',
        'jam_masuk' => '06:00',
        'jam_pulang' => '15:00',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);

    $this->actingAs($user)->get(route('petugas.dashboard'))
        ->assertOk()
        ->assertViewHas('jadwalEfektifHariIni', fn (JadwalEfektif $jadwal): bool => $jadwal->sumber === 'kalender'
            && $jadwal->status === JadwalPegawai::STATUS_LIBUR)
        ->assertSee('Libur')
        ->assertDontSee('06:00');
});

test('laporan absensi menampilkan status yang sama dengan hasil JadwalEfektifService', function () {
    CarbonImmutable::setTestNow('2026-01-08 07:00:00', config('app.timezone'));
    $admin = konsistenAdmin();

    $alpaP = Pegawai::factory()->create(['nama' => 'Rekap Alpa', 'status' => Pegawai::STATUS_ACTIVE]);
    $hadirP = Pegawai::factory()->create(['nama' => 'Rekap Hadir', 'status' => Pegawai::STATUS_ACTIVE]);

    PolaJadwal::factory()->create(['pegawai_id' => $alpaP->id, 'berlaku_mulai' => '2026-01-05']);
    PolaJadwal::factory()->create(['pegawai_id' => $hadirP->id, 'berlaku_mulai' => '2026-01-05']);
    Absensi::factory()->create([
        'pegawai_id' => $hadirP->id,
        'tanggal' => '2026-01-08',
        'status' => Absensi::STATUS_HADIR,
    ]);

    $rows = app(JadwalEfektifService::class)->rekapHarian(CarbonImmutable::parse('2026-01-08'));

    expect($rows->firstWhere('pegawai.id', $alpaP->id)['status'])->toBe(Absensi::STATUS_ALPA);
    expect($rows->firstWhere('pegawai.id', $hadirP->id)['status'])->toBe(Absensi::STATUS_HADIR);

    $this->actingAs($admin)->get(route('admin.laporan.absensi', [
        'tanggal_awal' => '2026-01-08',
        'tanggal_akhir' => '2026-01-08',
    ]))
        ->assertOk()
        ->assertSee('Rekap Alpa')
        ->assertSee('Alpa')
        ->assertSee('Rekap Hadir')
        ->assertSee('Hadir');
});

test('Jadwal Saya petugas menampilkan jadwal yang sama dengan JadwalEfektifService', function () {
    CarbonImmutable::setTestNow('2026-01-08 07:00:00', config('app.timezone'));
    [$user, $pegawai] = konsistenPetugas();
    PolaJadwal::factory()->create([
        'pegawai_id' => $pegawai->id,
        'berlaku_mulai' => '2026-01-05',
        'hari' => PolaJadwalFactory::polaHari(['07:00', '16:00']),
    ]);

    $kamis = CarbonImmutable::parse('2026-01-08');
    $sabtu = CarbonImmutable::parse('2026-01-10');

    expect(app(JadwalEfektifService::class)->efektif($pegawai, $kamis)?->status)->toBe(JadwalPegawai::STATUS_TERJADWAL);
    expect(app(JadwalEfektifService::class)->efektif($pegawai, $sabtu)?->status)->toBe(JadwalPegawai::STATUS_LIBUR);

    $this->actingAs($user)->get(route('petugas.jadwal.index'))
        ->assertOk()
        ->assertSee('07:00 - 16:00')
        ->assertSee('Libur');
});

test('hari libur dari pola tidak dianggap alpa', function () {
    CarbonImmutable::setTestNow('2026-01-08 07:00:00', config('app.timezone'));
    $admin = konsistenAdmin();
    $pegawai = Pegawai::factory()->create(['nama' => 'Petugas Minggu', 'status' => Pegawai::STATUS_ACTIVE]);
    PolaJadwal::factory()->create([
        'pegawai_id' => $pegawai->id,
        'berlaku_mulai' => '2026-01-05',
        'hari' => PolaJadwalFactory::polaHari(['07:00', '16:00']),
    ]);

    $sabtu = CarbonImmutable::parse('2026-01-10');
    $service = app(JadwalEfektifService::class);

    $efektif = $service->efektif($pegawai, $sabtu);
    $statuses = $service->monitoringMingguan($sabtu)->first()['hari']->pluck('status');

    expect($efektif?->status)->toBe(JadwalPegawai::STATUS_LIBUR);
    expect($service->statusRekap($efektif, null))->toBe(JadwalPegawai::STATUS_LIBUR);
    expect($statuses->get(5))->toBe(JadwalPegawai::STATUS_LIBUR);

    $this->actingAs($admin)->get(route('admin.laporan.absensi', [
        'tanggal_awal' => '2026-01-10',
        'tanggal_akhir' => '2026-01-10',
    ]))
        ->assertOk()
        ->assertSee('Petugas Minggu')
        ->assertSee('Libur')
        ->assertDontSee('16:00');
});

test('override mengalahkan pola mingguan pada jadwal efektif', function () {
    CarbonImmutable::setTestNow('2026-01-08 07:00:00', config('app.timezone'));
    [$user, $pegawai] = konsistenPetugas();
    PolaJadwal::factory()->create([
        'pegawai_id' => $pegawai->id,
        'berlaku_mulai' => '2026-01-05',
        'hari' => PolaJadwalFactory::polaHari(['07:00', '16:00']),
    ]);
    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-08',
        'jam_masuk' => '09:00',
        'jam_pulang' => '18:00',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);

    $efektif = app(JadwalEfektifService::class)->efektif($pegawai, CarbonImmutable::parse('2026-01-08'));

    expect($efektif?->sumber)->toBe('override');
    expect($efektif?->jamMasuk->format('H:i'))->toBe('09:00');

    $this->actingAs($user)->get(route('petugas.dashboard'))
        ->assertOk()
        ->assertViewHas('jadwalEfektifHariIni', fn (JadwalEfektif $jadwal): bool => $jadwal->sumber === 'override'
            && $jadwal->jamMasuk?->format('H:i') === '09:00')
        ->assertSee('09:00');
});

test('tanpa override jadwal efektif kembali ke pola mingguan', function () {
    CarbonImmutable::setTestNow('2026-01-08 07:00:00', config('app.timezone'));
    [$user, $pegawai] = konsistenPetugas();
    PolaJadwal::factory()->create([
        'pegawai_id' => $pegawai->id,
        'berlaku_mulai' => '2026-01-05',
        'hari' => PolaJadwalFactory::polaHari(['07:00', '16:00']),
    ]);

    $efektif = app(JadwalEfektifService::class)->efektif($pegawai, CarbonImmutable::parse('2026-01-08'));

    expect($efektif?->sumber)->toBe('pola');
    expect($efektif?->jamMasuk->format('H:i'))->toBe('07:00');

    $this->actingAs($user)->get(route('petugas.dashboard'))
        ->assertOk()
        ->assertViewHas('jadwalEfektifHariIni', fn (JadwalEfektif $jadwal): bool => $jadwal->sumber === 'pola'
            && $jadwal->jamMasuk?->format('H:i') === '07:00');
});
