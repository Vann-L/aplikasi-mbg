<?php

use App\Models\JadwalPegawai;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pegawaiData(array $overrides = []): array
{
    return array_merge([
        'id_pegawai' => 'PEG-0001',
        'nama' => 'Budi Santoso',
        'jabatan' => 'Staff Operasional',
        'bagian' => 'Distribusi',
        'no_hp' => '081234567890',
        'status' => Pegawai::STATUS_ACTIVE,
    ], $overrides);
}

test('admin can view pegawai index', function () {
    $admin = User::factory()->admin()->create();
    Pegawai::factory()->create(['nama' => 'Budi Santoso']);

    $this->actingAs($admin)->get(route('admin.pegawai.index'))
        ->assertOk()
        ->assertSee('Budi Santoso');
});

test('pegawai index can be searched and filtered', function () {
    $admin = User::factory()->admin()->create();
    Pegawai::factory()->create(['nama' => 'Budi Santoso']);
    Pegawai::factory()->create(['nama' => 'Siti Aminah', 'status' => Pegawai::STATUS_INACTIVE]);

    $this->actingAs($admin)->get(route('admin.pegawai.index', ['search' => 'Siti']))
        ->assertOk()
        ->assertSee('Siti Aminah')
        ->assertDontSee('Budi Santoso');

    $this->actingAs($admin)->get(route('admin.pegawai.index', ['status' => Pegawai::STATUS_INACTIVE]))
        ->assertOk()
        ->assertSee('Siti Aminah')
        ->assertDontSee('Budi Santoso');
});

test('admin can create a pegawai', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.pegawai.store'), pegawaiData())
        ->assertRedirect(route('admin.pegawai.index'));

    $this->assertDatabaseHas('pegawai', [
        'id_pegawai' => 'PEG-0001',
        'nama' => 'Budi Santoso',
        'status' => Pegawai::STATUS_ACTIVE,
        'user_id' => null,
    ]);
});

test('pegawai creation requires valid data', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.pegawai.store'), [])
        ->assertSessionHasErrors(['id_pegawai', 'nama', 'status']);
});

test('pegawai creation rejects duplicate id_pegawai', function () {
    $admin = User::factory()->admin()->create();
    Pegawai::factory()->create(['id_pegawai' => 'PEG-0001']);

    $this->actingAs($admin)->post(route('admin.pegawai.store'), pegawaiData())
        ->assertSessionHasErrors('id_pegawai');
});

test('admin can view pegawai detail', function () {
    $admin = User::factory()->admin()->create();
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi Santoso']);

    $this->actingAs($admin)->get(route('admin.pegawai.show', $pegawai))
        ->assertOk()
        ->assertSee('Budi Santoso');
});

test('admin can update a pegawai', function () {
    $admin = User::factory()->admin()->create();
    $pegawai = Pegawai::factory()->create(['id_pegawai' => 'PEG-0001']);

    $this->actingAs($admin)->put(route('admin.pegawai.update', $pegawai), pegawaiData([
        'nama' => 'Budi Diperbarui',
        'status' => Pegawai::STATUS_INACTIVE,
    ]))->assertRedirect(route('admin.pegawai.index'));

    $this->assertDatabaseHas('pegawai', [
        'id' => $pegawai->id,
        'nama' => 'Budi Diperbarui',
        'status' => Pegawai::STATUS_INACTIVE,
    ]);
});

test('pegawai update keeps its own id_pegawai valid', function () {
    $admin = User::factory()->admin()->create();
    $pegawai = Pegawai::factory()->create(['id_pegawai' => 'PEG-0001']);

    $this->actingAs($admin)->put(route('admin.pegawai.update', $pegawai), pegawaiData())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.pegawai.index'));
});

test('admin can delete a pegawai', function () {
    $admin = User::factory()->admin()->create();
    $pegawai = Pegawai::factory()->create();

    $this->actingAs($admin)->delete(route('admin.pegawai.destroy', $pegawai))
        ->assertRedirect(route('admin.pegawai.index'));

    $this->assertDatabaseMissing('pegawai', ['id' => $pegawai->id]);
});

test('pegawai with jadwal cannot be deleted', function () {
    $admin = User::factory()->admin()->create();
    $pegawai = Pegawai::factory()->create();
    JadwalPegawai::factory()->create(['pegawai_id' => $pegawai->id]);

    $this->actingAs($admin)->delete(route('admin.pegawai.destroy', $pegawai))
        ->assertRedirect(route('admin.pegawai.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('pegawai', ['id' => $pegawai->id]);
});

test('kepala can view pegawai but cannot manage it', function () {
    $kepala = User::factory()->kepala()->create();
    $pegawai = Pegawai::factory()->create();

    $this->actingAs($kepala)->get(route('admin.pegawai.index'))->assertOk();
    $this->actingAs($kepala)->get(route('admin.pegawai.show', $pegawai))->assertOk();
    $this->actingAs($kepala)->get(route('admin.pegawai.create'))->assertForbidden();
    $this->actingAs($kepala)->post(route('admin.pegawai.store'), pegawaiData())->assertForbidden();
    $this->actingAs($kepala)->get(route('admin.pegawai.edit', $pegawai))->assertForbidden();
    $this->actingAs($kepala)->put(route('admin.pegawai.update', $pegawai), pegawaiData())->assertForbidden();
    $this->actingAs($kepala)->delete(route('admin.pegawai.destroy', $pegawai))->assertForbidden();
});

test('petugas cannot access pegawai module', function () {
    $petugas = User::factory()->petugas()->create();

    $this->actingAs($petugas)->get(route('admin.pegawai.index'))->assertForbidden();
    $this->actingAs($petugas)->get(route('admin.pegawai.create'))->assertForbidden();
});

test('guest is redirected to login from pegawai module', function () {
    $this->get(route('admin.pegawai.index'))->assertRedirect(route('login'));
});
