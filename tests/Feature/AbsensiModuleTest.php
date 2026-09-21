<?php

use App\Models\Absensi;
use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use App\Models\User;
use App\Services\AbsensiQrService;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    CarbonImmutable::setTestNow(null);
});

function absensiAdmin(): User
{
    return User::factory()->admin()->create();
}

function absensiKepala(): User
{
    return User::factory()->kepala()->create();
}

/**
 * @return array{user: User, pegawai: Pegawai}
 */
function absensiPetugas(): array
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

function absensiQr(): AbsensiQrService
{
    return app(AbsensiQrService::class);
}

test('generate menghasilkan token yang dapat diverifikasi pada window yang sama', function () {
    $now = CarbonImmutable::parse('2026-01-05 07:00:15');
    CarbonImmutable::setTestNow($now);

    $token = absensiQr()->generate();

    expect(absensiQr()->verify($token))->toBeTrue();
});

test('token dari window sebelumnya masih diterima sebagai masa tenggang', function () {
    $old = CarbonImmutable::parse('2026-01-05 07:00:05');
    $token = absensiQr()->generate($old);

    CarbonImmutable::setTestNow($old->addSeconds(10));

    expect(absensiQr()->verify($token))->toBeTrue();
});

test('token dari window yang lebih lama ditolak', function () {
    $old = CarbonImmutable::parse('2026-01-05 07:00:05');
    $token = absensiQr()->generate($old);

    CarbonImmutable::setTestNow($old->addSeconds(20));

    expect(absensiQr()->verify($token))->toBeFalse();
});

test('token yang diubah atau tidak lengkap ditolak', function () {
    $now = CarbonImmutable::parse('2026-01-05 07:00:15');
    CarbonImmutable::setTestNow($now);

    $token = absensiQr()->generate();
    [$window, $signature] = explode('.', $token);

    expect(absensiQr()->verify($window.'.'.$signature.'00'))->toBeFalse()
        ->and(absensiQr()->verify($window))->toBeFalse()
        ->and(absensiQr()->verify('abc.signature'))->toBeFalse()
        ->and(absensiQr()->verify($window.'.'.strrev($signature)))->toBeFalse();
});

test('token berganti setiap window berlangsung', function () {
    $first = CarbonImmutable::parse('2026-01-05 07:00:15');
    $second = CarbonImmutable::parse('2026-01-05 07:00:25');

    expect(absensiQr()->generate($first))->not->toBe(absensiQr()->generate($second));
});

test('admin dan kepala dapat membuka rekap absensi', function () {
    $admin = absensiAdmin();
    $kepala = absensiKepala();

    $this->actingAs($admin)->get(route('admin.absensi.index'))->assertOk();
    $this->actingAs($kepala)->get(route('kepala.absensi.index'))->assertOk();
});

test('petugas dilarang membuka rekap absensi admin', function () {
    $petugas = absensiPetugas();

    $this->actingAs($petugas['user'])->get(route('admin.absensi.index'))->assertForbidden();
});

test('guest diarahkan ke login untuk halaman absensi', function () {
    $this->get(route('admin.absensi.index'))->assertRedirect(route('login'));
});

test('admin dan kepala dapat membuka display QR absensi', function () {
    $admin = absensiAdmin();
    $kepala = absensiKepala();

    $this->actingAs($admin)->get(route('admin.absensi.qr'))
        ->assertOk()
        ->assertSee('QR Absensi');

    $this->actingAs($kepala)->get(route('kepala.absensi.qr'))
        ->assertOk()
        ->assertSee('QR Absensi');
});

test('endpoint token mengembalikan token QR dan url scan petugas', function () {
    $admin = absensiAdmin();
    CarbonImmutable::setTestNow('2026-01-05 07:00:15');

    $response = $this->actingAs($admin)->getJson(route('admin.absensi.token'))->assertOk();

    $body = $response->json();

    expect($body)->toHaveKeys(['token', 'expires_in', 'scan_url'])
        ->and($body['expires_in'])->toBeBetween(1, 10)
        ->and($body['scan_url'])->toContain(route('petugas.absensi.index'))
        ->and(absensiQr()->verify($body['token']))->toBeTrue();
});

test('endpoint qr-img mengembalikan svg untuk admin dan kepala', function () {
    $admin = absensiAdmin();
    $kepala = absensiKepala();
    $token = absensiQr()->generate();

    $this->actingAs($admin)->get(route('admin.absensi.qr-img', ['token' => $token]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertSee('<svg', false);

    $this->actingAs($kepala)->get(route('kepala.absensi.qr-img', ['token' => $token]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertSee('<svg', false);
});

test('petugas dapat membuka halaman absensi saya', function () {
    $petugas = absensiPetugas();

    $this->actingAs($petugas['user'])->get(route('petugas.absensi.index'))
        ->assertOk()
        ->assertSee('Absensi Saya')
        ->assertSee('Scan QR untuk Absen');
});

test('halaman absensi petugas menampilkan scan kamera dan fallback manual', function () {
    $petugas = absensiPetugas();
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    $this->actingAs($petugas['user'])->get(route('petugas.absensi.index'))
        ->assertOk()
        ->assertSee('Mulai Scan QR')
        ->assertSee('data-qr-scanner', false)
        ->assertSee('data-scan-video', false)
        ->assertSee('Arahkan kamera ke QR Absensi')
        ->assertSee('data-scan-form', false)
        ->assertSee('name="token"', false)
        ->assertSee('Gunakan input manual jika kamera tidak tersedia.');
});

test('halaman absensi petugas menampilkan status absensi hari ini', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $petugas = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    $this->actingAs($petugas['user'])->get(route('petugas.absensi.index'))
        ->assertOk()
        ->assertSee('Belum Absen');

    Absensi::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '2026-01-05 07:00:00',
        'jam_pulang' => null,
        'status' => Absensi::STATUS_HADIR,
    ]);

    $this->actingAs($petugas['user'])->get(route('petugas.absensi.index'))
        ->assertOk()
        ->assertDontSee('Belum Absen')
        ->assertSee('Sudah Absen Masuk');

    $petugas['pegawai']->absensi()->first()->update(['jam_pulang' => '2026-01-05 16:00:00']);

    $this->actingAs($petugas['user'])->get(route('petugas.absensi.index'))
        ->assertOk()
        ->assertDontSee('Sudah Absen Masuk')
        ->assertSee('Sudah Absen Pulang');
});

test('admin dan kepala tidak menampilkan tombol scan kamera petugas', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $this->actingAs(absensiAdmin())->get(route('admin.absensi.index'))
        ->assertOk()
        ->assertDontSee('Mulai Scan QR')
        ->assertDontSee('data-scan-video', false);

    $this->actingAs(absensiKepala())->get(route('kepala.absensi.index'))
        ->assertOk()
        ->assertDontSee('Mulai Scan QR');

    $this->actingAs(absensiAdmin())->get(route('admin.absensi.qr'))
        ->assertOk()
        ->assertDontSee('Mulai Scan QR');

    $this->actingAs(absensiKepala())->get(route('kepala.absensi.qr'))
        ->assertOk()
        ->assertDontSee('Mulai Scan QR');
});

test('pesan hasil absensi ditampilkan setelah pemindaian', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $petugas = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    $this->actingAs($petugas['user'])->post(route('petugas.absensi.scan'), [
        'token' => absensiQr()->generate(),
    ])->assertRedirect(route('petugas.absensi.index'))
        ->assertSessionHas('status');

    $this->actingAs($petugas['user'])->get(route('petugas.absensi.index'))
        ->assertOk()
        ->assertSee('Absensi berhasil tercatat.')
        ->assertSee('Sudah Absen Masuk');

    $this->actingAs($petugas['user'])->post(route('petugas.absensi.scan'), [
        'token' => 'invalid.token',
    ])->assertSessionHas('error');

    $this->actingAs($petugas['user'])->get(route('petugas.absensi.index'))
        ->assertOk()
        ->assertSee('Token QR tidak valid atau sudah kedaluwarsa.');
});

test('absen masuk berhasil mencatat jam masuk dengan status hadir', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $petugas = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    $this->actingAs($petugas['user'])->post(route('petugas.absensi.scan'), [
        'token' => absensiQr()->generate(),
    ])->assertRedirect(route('petugas.absensi.index'))
        ->assertSessionHas('status');

    $absensi = $petugas['pegawai']->absensi()->first();

    expect($absensi)->not->toBeNull()
        ->and($absensi->status)->toBe(Absensi::STATUS_HADIR)
        ->and($absensi->jam_masuk?->format('H:i'))->toBe('07:00')
        ->and($absensi->jam_pulang)->toBeNull();
});

test('absen masuk dapat dilakukan melalui url qr dari kamera ponsel', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $petugas = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    $this->actingAs($petugas['user'])->get(route('petugas.absensi.scan-token', [
        'token' => absensiQr()->generate(),
    ]))->assertRedirect(route('petugas.absensi.index'))
        ->assertSessionHas('status');

    expect($petugas['pegawai']->absensi()->count())->toBe(1);
});

test('absen masuk mencatat terlambat saat lewat jam masuk', function () {
    CarbonImmutable::setTestNow('2026-01-05 08:30:00');

    $petugas = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    $this->actingAs($petugas['user'])->post(route('petugas.absensi.scan'), [
        'token' => absensiQr()->generate(),
    ])->assertSessionHas('status');

    expect($petugas['pegawai']->absensi()->first()->status)->toBe(Absensi::STATUS_TERLAMBAT);
});

test('absen masuk ditolak saat tidak ada jadwal kerja', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $petugas = absensiPetugas();

    $this->actingAs($petugas['user'])->post(route('petugas.absensi.scan'), [
        'token' => absensiQr()->generate(),
    ])->assertSessionHas('error');

    expect($petugas['pegawai']->absensi()->count())->toBe(0);
});

test('absen ditolak saat jadwal berstatus izin atau libur', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $petugas = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'status' => JadwalPegawai::STATUS_IZIN,
    ]);

    $this->actingAs($petugas['user'])->post(route('petugas.absensi.scan'), [
        'token' => absensiQr()->generate(),
    ])->assertSessionHas('error');

    $petugasLibur = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugasLibur['pegawai']->id,
        'tanggal' => '2026-01-05',
        'status' => JadwalPegawai::STATUS_LIBUR,
    ]);

    $this->actingAs($petugasLibur['user'])->post(route('petugas.absensi.scan'), [
        'token' => absensiQr()->generate(),
    ])->assertSessionHas('error');

    expect(Absensi::count())->toBe(0);
});

test('absen dengan token kadaluwarsa ditolak', function () {
    $old = CarbonImmutable::parse('2026-01-05 07:00:05');
    $token = absensiQr()->generate($old);

    CarbonImmutable::setTestNow('2026-01-05 07:00:35');

    $petugas = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    $this->actingAs($petugas['user'])->post(route('petugas.absensi.scan'), [
        'token' => $token,
    ])->assertSessionHas('error');

    expect($petugas['pegawai']->absensi()->count())->toBe(0);
});

test('absen dengan token yang tidak sah ditolak', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:15');

    $petugas = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    $this->actingAs($petugas['user'])->post(route('petugas.absensi.scan'), [
        'token' => '1234.salah',
    ])->assertSessionHas('error');

    expect($petugas['pegawai']->absensi()->count())->toBe(0);
});

test('absen pulang berhasil mengisi jam pulang pada catatan yang sama', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $petugas = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    $this->actingAs($petugas['user'])->post(route('petugas.absensi.scan'), [
        'token' => absensiQr()->generate(),
    ]);

    CarbonImmutable::setTestNow('2026-01-05 16:00:00');

    $this->actingAs($petugas['user'])->post(route('petugas.absensi.scan'), [
        'token' => absensiQr()->generate(),
    ])->assertSessionHas('status');

    $absensi = $petugas['pegawai']->absensi()->first();

    expect($absensi->jam_pulang?->format('H:i'))->toBe('16:00')
        ->and(Absensi::count())->toBe(1);
});

test('absen setelah catatan pulang ditolak', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $petugas = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    Absensi::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '2026-01-05 07:00:00',
        'jam_pulang' => '2026-01-05 16:00:00',
        'status' => Absensi::STATUS_HADIR,
    ]);

    $this->actingAs($petugas['user'])->post(route('petugas.absensi.scan'), [
        'token' => absensiQr()->generate(),
    ])->assertSessionHas('error');

    expect($petugas['pegawai']->absensi()->first()->jam_pulang?->format('H:i'))->toBe('16:00');
});

test('akun petugas tanpa data pegawai menampilkan pemberitahuan', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $user = User::factory()->petugas()->create();

    $this->actingAs($user)->get(route('petugas.absensi.index'))
        ->assertOk()
        ->assertSee('Akun belum terhubung ke pegawai');

    $this->actingAs($user)->post(route('petugas.absensi.scan'), [
        'token' => absensiQr()->generate(),
    ])->assertRedirect(route('petugas.absensi.index'))
        ->assertSessionHas('error');

    expect(Absensi::count())->toBe(0);
});

test('admin dilarang menggunakan alur scan petugas', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $admin = absensiAdmin();

    $this->actingAs($admin)->post(route('petugas.absensi.scan'), [
        'token' => absensiQr()->generate(),
    ])->assertForbidden();
});

test('rekap menampilkan alpa untuk jadwal terjadwal tanpa absensi', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $petugas = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    $this->actingAs(absensiAdmin())->get(route('admin.absensi.index'))
        ->assertOk()
        ->assertSee($petugas['pegawai']->nama)
        ->assertSee('Alpa');
});

test('rekap menampilkan status izin dan libur dari jadwal', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $izin = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $izin['pegawai']->id,
        'tanggal' => '2026-01-05',
        'status' => JadwalPegawai::STATUS_IZIN,
    ]);

    $libur = absensiPetugas();
    JadwalPegawai::factory()->create([
        'pegawai_id' => $libur['pegawai']->id,
        'tanggal' => '2026-01-05',
        'status' => JadwalPegawai::STATUS_LIBUR,
    ]);

    $this->actingAs(absensiAdmin())->get(route('admin.absensi.index'))
        ->assertOk()
        ->assertSee($izin['pegawai']->nama)
        ->assertSee('Izin')
        ->assertSee($libur['pegawai']->nama)
        ->assertSee('Libur');
});

test('rekap dapat difilter berdasarkan status absensi', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $admin = absensiAdmin();
    $hadir = absensiPetugas();
    $alpa = absensiPetugas();

    Absensi::factory()->create([
        'pegawai_id' => $hadir['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '2026-01-05 07:00:00',
        'jam_pulang' => null,
        'status' => Absensi::STATUS_HADIR,
    ]);

    JadwalPegawai::factory()->create([
        'pegawai_id' => $hadir['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    JadwalPegawai::factory()->create([
        'pegawai_id' => $alpa['pegawai']->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ]);

    $this->actingAs($admin)->get(route('admin.absensi.index', ['status' => Absensi::STATUS_HADIR]))
        ->assertOk()
        ->assertSee($hadir['pegawai']->nama)
        ->assertDontSee($alpa['pegawai']->nama);
});

test('absensi hanya satu per pegawai per tanggal', function () {
    $pegawai = absensiPetugas()['pegawai'];

    Absensi::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
    ]);

    expect(fn () => Absensi::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
    ]))->toThrow(UniqueConstraintViolationException::class);
});
