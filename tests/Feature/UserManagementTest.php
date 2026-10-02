<?php

use App\Models\Pegawai;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

function managAdmin(): User
{
    return User::factory()->admin()->create();
}

function managKepala(): User
{
    return User::factory()->kepala()->create();
}

function managPetugasUser(): User
{
    return User::factory()->petugas()->create();
}

function managPegawaiTanpaAkun(): Pegawai
{
    return Pegawai::factory()->create(['user_id' => null]);
}

test('admin dapat membuka daftar akun pengguna', function () {
    $admin = managAdmin();

    $this->actingAs($admin)->get(route('admin.user.index'))
        ->assertOk()
        ->assertSee('Pengguna');
});

test('admin dapat membuka form create akun', function () {
    $admin = managAdmin();

    $this->actingAs($admin)->get(route('admin.user.create'))
        ->assertOk()
        ->assertSee('Tambah Akun Pengguna')
        ->assertSee('Password');
});

test('kepala ditolak akses User Management', function () {
    $kepala = managKepala();

    $this->actingAs($kepala)->get(route('admin.user.index'))->assertForbidden();
    $this->actingAs($kepala)->get(route('admin.user.create'))->assertForbidden();
});

test('petugas ditolak akses User Management', function () {
    $petugas = managPetugasUser();

    $this->actingAs($petugas)->get(route('admin.user.index'))->assertForbidden();
});

test('guest diarahkan ke login untuk halaman User Management', function () {
    $this->get(route('admin.user.index'))->assertRedirect(route('login'));
});

test('admin dapat membuat akun pengguna valid dan menghubungkan pegawai', function () {
    $admin = managAdmin();
    $pegawai = managPegawaiTanpaAkun();

    $this->actingAs($admin)->post(route('admin.user.store'), [
        'name' => 'Petugas Baru',
        'email' => 'petugas.baru@mbg.test',
        'role' => 'petugas',
        'status' => 'active',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'pegawai_id' => $pegawai->id,
    ])->assertRedirect(route('admin.user.index'))->assertSessionHas('status');

    $user = User::where('email', 'petugas.baru@mbg.test')->first();

    expect($user)->not->toBeNull();
    expect($user->role)->toBe('petugas');
    expect($user->status)->toBe('active');
    expect(Hash::check('password123', $user->password))->toBeTrue();
    expect($pegawai->refresh()->user_id)->toBe($user->id);
});

test('email duplikat ditolak saat membuat akun', function () {
    $admin = managAdmin();
    $existing = managPetugasUser();

    $this->actingAs($admin)->post(route('admin.user.store'), [
        'name' => 'Petugas Duplikat',
        'email' => $existing->email,
        'role' => 'petugas',
        'status' => 'active',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('email');
});

test('role tidak valid ditolak saat membuat akun', function () {
    $admin = managAdmin();

    $this->actingAs($admin)->post(route('admin.user.store'), [
        'name' => 'Role Salah',
        'email' => 'role.salah@mbg.test',
        'role' => 'siswa',
        'status' => 'active',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('role');
});

test('status tidak valid ditolak saat membuat akun', function () {
    $admin = managAdmin();

    $this->actingAs($admin)->post(route('admin.user.store'), [
        'name' => 'Status Salah',
        'email' => 'status.salah@mbg.test',
        'role' => 'petugas',
        'status' => 'aktif',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('status');
});

test('konfirmasi password gagal ditolak saat membuat akun', function () {
    $admin = managAdmin();

    $this->actingAs($admin)->post(route('admin.user.store'), [
        'name' => 'Konfirmasi Salah',
        'email' => 'konfirmasi.salah@mbg.test',
        'role' => 'petugas',
        'status' => 'active',
        'password' => 'password123',
        'password_confirmation' => 'password456',
    ])->assertSessionHasErrors('password');
});

test('pegawai yang sudah memiliki akun tidak dapat dipasangkan lagi', function () {
    $admin = managAdmin();
    $owner = managPetugasUser();
    $linked = Pegawai::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($admin)->post(route('admin.user.store'), [
        'name' => 'Akun Baru',
        'email' => 'akun.baru@mbg.test',
        'role' => 'petugas',
        'status' => 'active',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'pegawai_id' => $linked->id,
    ])->assertSessionHasErrors('pegawai_id');

    expect($linked->refresh()->user_id)->toBe($owner->id);
});

test('password akun tersimpan dalam bentuk hash', function () {
    $admin = managAdmin();

    $this->actingAs($admin)->post(route('admin.user.store'), [
        'name' => 'Hash Test',
        'email' => 'hash.test@mbg.test',
        'role' => 'petugas',
        'status' => 'active',
        'password' => 'sandi12345',
        'password_confirmation' => 'sandi12345',
    ])->assertRedirect(route('admin.user.index'));

    $user = User::where('email', 'hash.test@mbg.test')->firstOrFail();

    expect($user->password)->not->toBe('sandi12345');
    expect(Hash::check('sandi12345', $user->password))->toBeTrue();
});

test('admin dapat mengubah email akun', function () {
    $admin = managAdmin();
    $target = managPetugasUser();

    $this->actingAs($admin)->put(route('admin.user.update', $target), [
        'name' => $target->name,
        'email' => 'email.baru@mbg.test',
        'role' => $target->role,
        'status' => $target->status,
    ])->assertRedirect(route('admin.user.index'))->assertSessionHas('status');

    expect($target->refresh()->email)->toBe('email.baru@mbg.test');
});

test('admin dapat mengubah role akun', function () {
    $admin = managAdmin();
    $target = managPetugasUser();

    $this->actingAs($admin)->put(route('admin.user.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'role' => 'kepala',
        'status' => $target->status,
    ])->assertRedirect(route('admin.user.index'));

    expect($target->refresh()->role)->toBe('kepala');
});

test('admin dapat menonaktifkan akun pengguna lain', function () {
    $admin = managAdmin();
    $target = managPetugasUser();

    $this->actingAs($admin)->put(route('admin.user.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'role' => $target->role,
        'status' => 'inactive',
    ])->assertRedirect(route('admin.user.index'));

    expect($target->refresh()->status)->toBe('inactive');
});

test('integritas pegawai tetap terjaga saat edit akun', function () {
    $admin = managAdmin();
    $owner = managPetugasUser();
    $pegawai1 = Pegawai::factory()->create(['user_id' => $owner->id]);
    $pegawai2 = managPegawaiTanpaAkun();
    $other = managPetugasUser();

    $this->actingAs($admin)->put(route('admin.user.update', $other), [
        'name' => $other->name,
        'email' => $other->email,
        'role' => $other->role,
        'status' => $other->status,
        'pegawai_id' => $pegawai1->id,
    ])->assertSessionHasErrors('pegawai_id');

    expect($pegawai1->refresh()->user_id)->toBe($owner->id);

    $this->actingAs($admin)->put(route('admin.user.update', $owner), [
        'name' => $owner->name,
        'email' => $owner->email,
        'role' => $owner->role,
        'status' => $owner->status,
        'pegawai_id' => $pegawai2->id,
    ])->assertRedirect(route('admin.user.index'));

    expect($pegawai2->refresh()->user_id)->toBe($owner->id);
    expect($pegawai1->refresh()->user_id)->toBeNull();
});

test('admin dapat membuka form reset password akun', function () {
    $admin = managAdmin();
    $target = managPetugasUser();

    $this->actingAs($admin)->get(route('admin.users.reset', $target))
        ->assertOk()
        ->assertSee('Reset Password');
});

test('admin dapat mereset password akun', function () {
    $admin = managAdmin();
    $target = managPetugasUser();

    $this->actingAs($admin)->post(route('admin.users.reset-password', $target), [
        'password' => 'passwordbaru1',
        'password_confirmation' => 'passwordbaru1',
    ])->assertRedirect(route('admin.user.index'))->assertSessionHas('status');

    expect(Hash::check('passwordbaru1', $target->refresh()->password))->toBeTrue();
});

test('password lama tidak lagi berlaku setelah reset', function () {
    $admin = managAdmin();
    $target = User::factory()->petugas()->create(['password' => 'passwordlama']);

    $this->actingAs($admin)->post(route('admin.users.reset-password', $target), [
        'password' => 'passwordbaru1',
        'password_confirmation' => 'passwordbaru1',
    ]);

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();

    $this->post(route('login'), [
        'email' => $target->email,
        'password' => 'passwordlama',
    ])->assertSessionHasErrors('email');

    $this->post(route('login'), [
        'email' => $target->email,
        'password' => 'passwordbaru1',
    ])->assertRedirect(route('petugas.dashboard'));

    $this->assertAuthenticatedAs($target);
});

test('admin tidak dapat menonaktifkan akun sendiri', function () {
    $admin = managAdmin();

    $this->actingAs($admin)->put(route('admin.user.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => 'admin',
        'status' => 'inactive',
    ])->assertRedirect(route('admin.user.edit', $admin))->assertSessionHas('error');

    expect($admin->refresh()->status)->toBe('active');
    expect($admin->refresh()->role)->toBe('admin');

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
});

test('tidak ada hard delete user pada lifecycle normal', function () {
    $admin = managAdmin();

    expect(Route::has('admin.users.destroy'))->toBeFalse();

    $target = managPetugasUser();

    $this->actingAs($admin)->delete('/admin/users/'.$target->id)->assertMethodNotAllowed();

    expect(User::find($target->id))->not->toBeNull();
});

test('login existing tetap berfungsi', function () {
    $user = User::factory()->petugas()->create(['password' => 'password']);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('petugas.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('redirect role existing tetap berfungsi', function () {
    $admin = User::factory()->admin()->create(['password' => 'password']);

    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
});

test('akun demo existing tetap valid', function () {
    $this->seed(DemoDataSeeder::class);

    $admin = User::where('email', 'admin@mbg.test')->firstOrFail();
    $kepala = User::where('email', 'kepala@mbg.test')->firstOrFail();
    $petugas = User::where('email', 'petugas@mbg.test')->firstOrFail();

    expect($admin->role)->toBe('admin');
    expect($admin->pegawai)->toBeNull();
    expect($kepala->pegawai->id_pegawai)->toBe('DEMO-K001');
    expect($petugas->pegawai->id_pegawai)->toBe('DEMO-K003');

    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->post(route('logout'));

    $this->post(route('login'), [
        'email' => $petugas->email,
        'password' => 'password',
    ])->assertRedirect(route('petugas.dashboard'));
});
