<?php

use App\Models\Absensi;
use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use App\Services\AbsensiQrService;
use App\Services\AbsensiService;
use App\Services\JadwalEfektifService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('operational clock follows the application timezone', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta');
    expect(date_default_timezone_get())->toBe('Asia/Jakarta');
    expect(now()->getOffset())->toBe(7 * 3600);
    expect(today()->toDateString())->toBe(CarbonImmutable::now('Asia/Jakarta')->toDateString());
});

test('today jadwal is matched in the application timezone', function () {
    $pegawai = Pegawai::factory()->create();

    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => today(),
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);

    $jadwal = $pegawai->jadwalPegawai()->whereDate('tanggal', today())->first();

    expect($jadwal)->not->toBeNull();
    expect($jadwal->tanggal->toDateString())->toBe(CarbonImmutable::now('Asia/Jakarta')->toDateString());
});

test('terlambat detection compares the scan against the jadwal in the application timezone', function () {
    $pegawai = Pegawai::factory()->create();
    $now = CarbonImmutable::now();

    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => $now->toDateString(),
        'jam_masuk' => $now->copy()->subMinute(),
        'jam_pulang' => '16:00',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);

    $qr = new AbsensiQrService;
    $result = app(AbsensiService::class)->scan($pegawai, $qr->generate($now), $now);

    expect($result['status'])->toBe(Absensi::STATUS_TERLAMBAT);

    $absensi = $pegawai->absensiPada($now);

    expect($absensi)->not->toBeNull();
    expect($absensi->tanggal->toDateString())->toBe($now->toDateString());
    expect($absensi->jam_masuk?->format('H:i'))->toBe($now->format('H:i'));
});

test('alpa is derived from a today effective schedule without attendance in the application timezone', function () {
    $pegawai = Pegawai::factory()->create(['status' => Pegawai::STATUS_ACTIVE]);

    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => today(),
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
        'status' => JadwalPegawai::STATUS_TERJADWAL,
    ]);

    $service = app(JadwalEfektifService::class);
    $efektif = $service->efektif($pegawai, CarbonImmutable::today());

    expect($efektif)->not->toBeNull();
    expect($service->statusRekap($efektif, null))->toBe(Absensi::STATUS_ALPA);
});
