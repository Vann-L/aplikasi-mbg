<?php

use App\Models\Absensi;
use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use App\Models\User;
use App\Services\AbsensiQrService;
use App\Services\AbsensiService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function () {
    CarbonImmutable::setTestNow(null);
});

function jadwalData(array $overrides = []): array
{
    return array_merge([
        'pegawai_id' => 1,
        'tanggal' => '2026-01-05',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
        'catatan' => null,
    ], $overrides);
}

function jadwalAdmin(): User
{
    return User::factory()->admin()->create();
}

/**
 * @return array{user: User, pegawai: Pegawai}
 */
function jadwalPetugas(): array
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

test('admin dapat membuka index jadwal', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi Santoso', 'status' => Pegawai::STATUS_ACTIVE]);
    JadwalPegawai::factory()->create(['pegawai_id' => $pegawai->id, 'tanggal' => '2026-01-05']);

    $this->actingAs($admin)->get(route('admin.jadwal.index'))
        ->assertOk()
        ->assertSee('Budi Santoso');
});

test('index jadwal dapat difilter berdasarkan tanggal, pegawai, dan status', function () {
    $admin = jadwalAdmin();
    $budi = Pegawai::factory()->create(['nama' => 'Budi Santoso', 'status' => Pegawai::STATUS_ACTIVE]);
    $siti = Pegawai::factory()->create(['nama' => 'Siti Aminah', 'status' => Pegawai::STATUS_ACTIVE]);
    JadwalPegawai::factory()->create(['pegawai_id' => $budi->id, 'tanggal' => '2026-01-05']);
    JadwalPegawai::factory()->create([
        'pegawai_id' => $siti->id,
        'tanggal' => '2026-01-06',
        'status' => JadwalPegawai::STATUS_IZIN,
    ]);

    $this->actingAs($admin)->get(route('admin.jadwal.index', ['tanggal' => '2026-01-05']))
        ->assertOk()
        ->assertSee('05/01/2026')
        ->assertDontSee('06/01/2026');

    $this->actingAs($admin)->get(route('admin.jadwal.index', ['pegawai_id' => $siti->id]))
        ->assertOk()
        ->assertSee('06/01/2026')
        ->assertDontSee('05/01/2026');

    $this->actingAs($admin)->get(route('admin.jadwal.index', ['status' => JadwalPegawai::STATUS_IZIN]))
        ->assertOk()
        ->assertSee('06/01/2026')
        ->assertDontSee('05/01/2026');
});

test('admin dapat membuka form create jadwal', function () {
    $admin = jadwalAdmin();
    Pegawai::factory()->create(['nama' => 'Budi Santoso', 'status' => Pegawai::STATUS_ACTIVE]);
    Pegawai::factory()->create(['nama' => 'Siti Nonaktif', 'status' => Pegawai::STATUS_INACTIVE]);

    $this->actingAs($admin)->get(route('admin.jadwal.create'))
        ->assertOk()
        ->assertSee('Tambah Jadwal')
        ->assertSee('Budi Santoso')
        ->assertDontSee('Siti Nonaktif');
});

test('admin dapat membuat jadwal terjadwal', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);

    $this->actingAs($admin)->post(route('admin.jadwal.store'), jadwalData([
        'pegawai_id' => $pegawai->id,
    ]))->assertRedirect(route('admin.jadwal.index'));

    $jadwal = JadwalPegawai::query()->where('pegawai_id', $pegawai->id)->first();

    expect($jadwal)->not->toBeNull()
        ->and($jadwal->tanggal?->format('Y-m-d'))->toBe('2026-01-05')
        ->and($jadwal->jam_masuk?->format('H:i'))->toBe('07:00')
        ->and($jadwal->jam_pulang?->format('H:i'))->toBe('16:00')
        ->and($jadwal->status)->toBe(JadwalPegawai::STATUS_TERJADWAL);
});

test('jadwal terjadwal wajib mengisi jam masuk dan jam pulang', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);

    $this->actingAs($admin)->post(route('admin.jadwal.store'), jadwalData([
        'pegawai_id' => $pegawai->id,
        'jam_masuk' => null,
        'jam_pulang' => null,
    ]))->assertSessionHasErrors(['jam_masuk', 'jam_pulang']);

    $this->assertDatabaseCount('jadwal_pegawai', 0);
});

test('jam pulang harus lebih besar dari jam masuk', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);

    $this->actingAs($admin)->post(route('admin.jadwal.store'), jadwalData([
        'pegawai_id' => $pegawai->id,
        'jam_masuk' => '09:00',
        'jam_pulang' => '08:00',
    ]))->assertSessionHasErrors('jam_pulang');

    $this->assertDatabaseCount('jadwal_pegawai', 0);
});

test('status izin atau libur menolak jam yang terisi', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);

    $this->actingAs($admin)->post(route('admin.jadwal.store'), jadwalData([
        'pegawai_id' => $pegawai->id,
        'status' => JadwalPegawai::STATUS_IZIN,
        'jam_masuk' => '07:00',
        'jam_pulang' => null,
    ]))->assertSessionHasErrors('jam_masuk');

    $this->actingAs($admin)->post(route('admin.jadwal.store'), jadwalData([
        'pegawai_id' => $pegawai->id,
        'status' => JadwalPegawai::STATUS_LIBUR,
        'jam_masuk' => null,
        'jam_pulang' => '16:00',
    ]))->assertSessionHasErrors('jam_pulang');

    $this->assertDatabaseCount('jadwal_pegawai', 0);
});

test('status izin dapat dibuat tanpa jam', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);

    $this->actingAs($admin)->post(route('admin.jadwal.store'), jadwalData([
        'pegawai_id' => $pegawai->id,
        'status' => JadwalPegawai::STATUS_IZIN,
        'jam_masuk' => null,
        'jam_pulang' => null,
    ]))->assertRedirect(route('admin.jadwal.index'));

    $this->assertDatabaseHas('jadwal_pegawai', [
        'pegawai_id' => $pegawai->id,
        'status' => JadwalPegawai::STATUS_IZIN,
        'jam_masuk' => null,
        'jam_pulang' => null,
    ]);
});

test('jadwal duplikat untuk pegawai dan tanggal yang sama ditolak dengan pesan ramah', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    JadwalPegawai::factory()->create(['pegawai_id' => $pegawai->id, 'tanggal' => '2026-01-05']);

    $this->actingAs($admin)->post(route('admin.jadwal.store'), jadwalData([
        'pegawai_id' => $pegawai->id,
    ]))->assertSessionHasErrors([
        'tanggal' => 'Pegawai sudah memiliki jadwal pada tanggal tersebut.',
    ]);

    $this->assertDatabaseCount('jadwal_pegawai', 1);
});

test('jadwal menolak pegawai yang tidak aktif', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_INACTIVE]);

    $this->actingAs($admin)->post(route('admin.jadwal.store'), jadwalData([
        'pegawai_id' => $pegawai->id,
    ]))->assertSessionHasErrors(['pegawai_id' => 'Pegawai tidak valid atau tidak aktif.']);

    $this->assertDatabaseCount('jadwal_pegawai', 0);
});

test('admin dapat membuka detail jadwal', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi Santoso', 'status' => Pegawai::STATUS_ACTIVE]);
    $jadwal = JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
        'catatan' => 'Piket',
    ]);

    $this->actingAs($admin)->get(route('admin.jadwal.show', $jadwal))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('Piket');
});

test('admin hanya dapat mengubah catatan pada jadwal yang sudah memiliki absensi', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $jadwal = JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
    ]);
    Absensi::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
        'jam_masuk' => '2026-01-05 07:00:00',
        'jam_pulang' => '2026-01-05 16:00:00',
        'status' => Absensi::STATUS_HADIR,
    ]);

    $this->actingAs($admin)->from(route('admin.jadwal.edit', $jadwal))->put(route('admin.jadwal.update', $jadwal), [
        'catatan' => 'Catatan baru',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.jadwal.index'));

    $jadwal->refresh();

    expect($jadwal->catatan)->toBe('Catatan baru')
        ->and($jadwal->pegawai_id)->toBe($pegawai->id)
        ->and($jadwal->tanggal?->format('Y-m-d'))->toBe('2026-01-05')
        ->and($jadwal->jam_masuk?->format('H:i'))->toBe('07:00')
        ->and($jadwal->jam_pulang?->format('H:i'))->toBe('16:00')
        ->and($jadwal->status)->toBe(JadwalPegawai::STATUS_TERJADWAL);
});

test('jadwal dengan absensi menolak perubahan tanggal', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $jadwal = JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
    ]);
    Absensi::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
        'status' => Absensi::STATUS_HADIR,
    ]);

    $this->actingAs($admin)->from(route('admin.jadwal.edit', $jadwal))->put(route('admin.jadwal.update', $jadwal), [
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-06',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ])->assertSessionHasErrors([
        'tanggal' => 'Jadwal yang sudah memiliki absensi tidak dapat diubah pegawai, tanggal, jam, atau statusnya. Hanya catatan yang boleh diperbarui.',
    ]);

    expect(JadwalPegawai::find($jadwal->id)?->tanggal?->format('Y-m-d'))->toBe('2026-01-05');
});

test('jadwal dengan absensi menolak perubahan status', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $jadwal = JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
    ]);
    Absensi::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
        'status' => Absensi::STATUS_HADIR,
    ]);

    $this->actingAs($admin)->from(route('admin.jadwal.edit', $jadwal))->put(route('admin.jadwal.update', $jadwal), [
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
        'status' => JadwalPegawai::STATUS_LIBUR,
        'jam_masuk' => null,
        'jam_pulang' => null,
    ])->assertSessionHasErrors('tanggal');

    $this->assertDatabaseHas('jadwal_pegawai', [
        'id' => $jadwal->id,
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);
});

test('admin dapat menghapus jadwal tanpa absensi', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $jadwal = JadwalPegawai::factory()->create(['pegawai_id' => $pegawai->id]);

    $this->actingAs($admin)->delete(route('admin.jadwal.destroy', $jadwal))
        ->assertRedirect(route('admin.jadwal.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('jadwal_pegawai', ['id' => $jadwal->id]);
});

test('jadwal dengan absensi tidak dapat dihapus', function () {
    $admin = jadwalAdmin();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $jadwal = JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
    ]);
    Absensi::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-01-05',
        'status' => Absensi::STATUS_HADIR,
    ]);

    $this->actingAs($admin)->delete(route('admin.jadwal.destroy', $jadwal))
        ->assertRedirect(route('admin.jadwal.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('jadwal_pegawai', ['id' => $jadwal->id]);
});

test('kepala hanya membaca jadwal dan tidak dapat mengelolanya', function () {
    $kepala = User::factory()->kepala()->create();
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $jadwal = JadwalPegawai::factory()->create(['pegawai_id' => $pegawai->id]);

    $this->actingAs($kepala)->get(route('kepala.jadwal.index'))->assertOk();
    $this->actingAs($kepala)->get(route('kepala.jadwal.show', $jadwal))->assertOk();
    $this->actingAs($kepala)->get(route('admin.jadwal.create'))->assertForbidden();
    $this->actingAs($kepala)->post(route('admin.jadwal.store'), jadwalData([
        'pegawai_id' => $pegawai->id,
    ]))->assertForbidden();
    $this->actingAs($kepala)->get(route('admin.jadwal.edit', $jadwal))->assertForbidden();
    $this->actingAs($kepala)->put(route('admin.jadwal.update', $jadwal), jadwalData([
        'pegawai_id' => $pegawai->id,
    ]))->assertForbidden();
    $this->actingAs($kepala)->delete(route('admin.jadwal.destroy', $jadwal))->assertForbidden();
});

test('petugas hanya melihat jadwal miliknya sendiri', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $petugas = jadwalPetugas();
    Pegawai::factory()->create(['nama' => 'Pegawai Lain', 'status' => Pegawai::STATUS_ACTIVE]);
    JadwalPegawai::factory()->create([
        'pegawai_id' => $petugas['pegawai']->id,
        'tanggal' => '2026-01-05',
    ]);

    $this->actingAs($petugas['user'])->get(route('petugas.jadwal.index'))
        ->assertOk()
        ->assertSee('Jadwal Saya')
        ->assertSee($petugas['pegawai']->nama);

    $this->actingAs($petugas['user'])->get(route('admin.jadwal.index'))->assertForbidden();
});

test('petugas tidak melihat jadwal pegawai lain', function () {
    CarbonImmutable::setTestNow('2026-01-05 07:00:00');

    $petugas = jadwalPetugas();
    $lain = Pegawai::factory()->create(['nama' => 'Pegawai Lain', 'status' => Pegawai::STATUS_ACTIVE]);
    JadwalPegawai::factory()->create([
        'pegawai_id' => $lain->id,
        'tanggal' => '2026-01-05',
    ]);

    $this->actingAs($petugas['user'])->get(route('petugas.jadwal.index'))
        ->assertOk()
        ->assertDontSee('Pegawai Lain');
});

test('jadwal yang dibuat lewat UI dapat dipakai untuk absensi masuk dan terlambat', function () {
    $admin = jadwalAdmin();
    $petugas = jadwalPetugas();

    $this->actingAs($admin)->post(route('admin.jadwal.store'), jadwalData([
        'pegawai_id' => $petugas['pegawai']->id,
    ]))->assertRedirect(route('admin.jadwal.index'));

    CarbonImmutable::setTestNow('2026-01-05 06:55:00');
    $service = app(AbsensiService::class);

    $hasil = $service->scan($petugas['pegawai'], app(AbsensiQrService::class)->generate());
    expect($hasil['status'])->toBe(Absensi::STATUS_HADIR);

    CarbonImmutable::setTestNow('2026-01-05 07:10:00');
    $pegawaiTerlambat = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);
    $this->actingAs($admin)->post(route('admin.jadwal.store'), jadwalData([
        'pegawai_id' => $pegawaiTerlambat->id,
    ]))->assertRedirect(route('admin.jadwal.index'));

    $hasilTerlambat = $service->scan($pegawaiTerlambat, app(AbsensiQrService::class)->generate());
    expect($hasilTerlambat['status'])->toBe(Absensi::STATUS_TERLAMBAT);
});

test('akun petugas tanpa data pegawai menampilkan pemberitahuan', function () {
    $user = User::factory()->petugas()->create();

    $this->actingAs($user)->get(route('petugas.jadwal.index'))
        ->assertOk()
        ->assertSee('Akun belum terhubung ke pegawai');
});

test('guest diarahkan ke login untuk modul jadwal', function () {
    $this->get(route('admin.jadwal.index'))->assertRedirect(route('login'));
    $this->get(route('petugas.jadwal.index'))->assertRedirect(route('login'));
});
