<?php

use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('jadwal pegawai maps to the agreed table name', function () {
    expect((new JadwalPegawai)->getTable())->toBe('jadwal_pegawai');
});

test('pegawai has many jadwal on different dates and jadwal belongs to pegawai', function () {
    $pegawai = Pegawai::factory()->create();
    $jadwal = JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-09-18',
    ]);
    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-09-19',
    ]);

    expect($jadwal->pegawai->id)->toBe($pegawai->id);
    expect($pegawai->jadwalPegawai)->toHaveCount(2);
});

test('one pegawai cannot have two jadwal on the same date', function () {
    $pegawai = Pegawai::factory()->create();

    JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-09-18',
    ]);

    expect(fn () => JadwalPegawai::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-09-18',
    ]))->toThrow(QueryException::class);
});

test('jadwal terjadwal stores jam masuk and jam pulang', function () {
    $jadwal = JadwalPegawai::factory()->create([
        'status' => 'terjadwal',
        'jam_masuk' => '07:00',
        'jam_pulang' => '16:00',
    ])->refresh();

    expect($jadwal->status)->toBe('terjadwal');
    expect($jadwal->jam_masuk->format('H:i'))->toBe('07:00');
    expect($jadwal->jam_pulang->format('H:i'))->toBe('16:00');
});

test('jadwal izin may have null jam', function () {
    $jadwal = JadwalPegawai::factory()->create([
        'status' => 'izin',
        'jam_masuk' => null,
        'jam_pulang' => null,
    ])->refresh();

    expect($jadwal->status)->toBe('izin');
    expect($jadwal->jam_masuk)->toBeNull();
    expect($jadwal->jam_pulang)->toBeNull();
});

test('jadwal libur may have null jam', function () {
    $jadwal = JadwalPegawai::factory()->create([
        'status' => 'libur',
        'jam_masuk' => null,
        'jam_pulang' => null,
    ])->refresh();

    expect($jadwal->status)->toBe('libur');
    expect($jadwal->jam_masuk)->toBeNull();
    expect($jadwal->jam_pulang)->toBeNull();
});

test('jadwal status defaults to terjadwal', function () {
    $pegawai = Pegawai::factory()->create();

    $jadwal = JadwalPegawai::create([
        'pegawai_id' => $pegawai->id,
        'tanggal' => '2026-09-18',
    ])->refresh();

    expect($jadwal->status)->toBe('terjadwal');
});

test('foreign key blocks orphan jadwal records', function () {
    expect(fn () => JadwalPegawai::factory()->create(['pegawai_id' => 999999]))
        ->toThrow(QueryException::class);
});
