<?php

use App\Models\Menu;
use App\Models\Produksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function menuData(array $overrides = []): array
{
    return array_merge([
        'nama' => 'Nasi Ayam Sayur',
        'deskripsi' => 'Menu siang dengan protein dan sayur.',
        'status' => Menu::STATUS_ACTIVE,
    ], $overrides);
}

test('admin can view menu index', function () {
    $admin = User::factory()->admin()->create();
    Menu::factory()->create(['nama' => 'Nasi Ayam Sayur']);

    $this->actingAs($admin)->get(route('admin.menu.index'))
        ->assertOk()
        ->assertSee('Nasi Ayam Sayur');
});

test('menu index can be searched and filtered', function () {
    $admin = User::factory()->admin()->create();
    Menu::factory()->create(['nama' => 'Nasi Ayam Sayur']);
    Menu::factory()->create(['nama' => 'Bubur Kacang Hijau', 'status' => Menu::STATUS_INACTIVE]);

    $this->actingAs($admin)->get(route('admin.menu.index', ['search' => 'Bubur']))
        ->assertOk()
        ->assertSee('Bubur Kacang Hijau')
        ->assertDontSee('Nasi Ayam Sayur');

    $this->actingAs($admin)->get(route('admin.menu.index', ['status' => Menu::STATUS_INACTIVE]))
        ->assertOk()
        ->assertSee('Bubur Kacang Hijau')
        ->assertDontSee('Nasi Ayam Sayur');
});

test('admin can create a menu without foto', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.menu.store'), menuData())
        ->assertRedirect(route('admin.menu.index'));

    $this->assertDatabaseHas('menus', [
        'nama' => 'Nasi Ayam Sayur',
        'status' => Menu::STATUS_ACTIVE,
        'foto' => null,
    ]);
});

test('admin can create a menu with foto', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.menu.store'), menuData([
        'foto' => UploadedFile::fake()->image('menu.jpg', 120, 120),
    ]))->assertRedirect(route('admin.menu.index'));

    $menu = Menu::firstOrFail();
    expect($menu->foto)->not->toBeNull();
    Storage::disk('public')->assertExists($menu->foto);
});

test('menu creation requires nama', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.menu.store'), menuData(['nama' => '']))
        ->assertSessionHasErrors('nama');
});

test('menu creation rejects invalid foto type', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.menu.store'), menuData([
        'foto' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
    ]))->assertSessionHasErrors('foto');
});

test('admin can view menu detail', function () {
    $admin = User::factory()->admin()->create();
    $menu = Menu::factory()->create(['nama' => 'Nasi Ayam Sayur']);

    $this->actingAs($admin)->get(route('admin.menu.show', $menu))
        ->assertOk()
        ->assertSee('Nasi Ayam Sayur');
});

test('admin can update a menu', function () {
    $admin = User::factory()->admin()->create();
    $menu = Menu::factory()->create(['nama' => 'Nasi Ayam Sayur']);

    $this->actingAs($admin)->put(route('admin.menu.update', $menu), menuData([
        'nama' => 'Nasi Ayam Bakar',
        'status' => Menu::STATUS_INACTIVE,
    ]))->assertRedirect(route('admin.menu.index'));

    $this->assertDatabaseHas('menus', [
        'id' => $menu->id,
        'nama' => 'Nasi Ayam Bakar',
        'status' => Menu::STATUS_INACTIVE,
    ]);
});

test('admin can replace a menu foto', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $oldPath = UploadedFile::fake()->image('lama.jpg', 100, 100)->store('menu-foto', 'public');
    $menu = Menu::factory()->create(['foto' => $oldPath]);

    $this->actingAs($admin)->put(route('admin.menu.update', $menu), menuData([
        'foto' => UploadedFile::fake()->image('baru.jpg', 100, 100),
    ]))->assertRedirect(route('admin.menu.index'));

    $menu->refresh();
    expect($menu->foto)->not->toBe($oldPath);
    Storage::disk('public')->assertExists($menu->foto);
});

test('replacing a menu foto deletes the old file', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $oldPath = UploadedFile::fake()->image('lama.jpg', 100, 100)->store('menu-foto', 'public');
    $menu = Menu::factory()->create(['foto' => $oldPath]);

    $this->actingAs($admin)->put(route('admin.menu.update', $menu), menuData([
        'foto' => UploadedFile::fake()->image('baru.jpg', 100, 100),
    ]));

    Storage::disk('public')->assertMissing($oldPath);
});

test('admin can delete a menu without produksi', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $path = UploadedFile::fake()->image('menu.jpg', 100, 100)->store('menu-foto', 'public');
    $menu = Menu::factory()->create(['foto' => $path]);

    $this->actingAs($admin)->delete(route('admin.menu.destroy', $menu))
        ->assertRedirect(route('admin.menu.index'));

    $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
    Storage::disk('public')->assertMissing($path);
});

test('menu used by produksi cannot be deleted', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $menu = Menu::factory()->create();
    Produksi::factory()->create(['menu_id' => $menu->id]);

    $this->actingAs($admin)->delete(route('admin.menu.destroy', $menu))
        ->assertRedirect(route('admin.menu.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('menus', ['id' => $menu->id]);
});

test('kepala can view menu but cannot manage it', function () {
    $kepala = User::factory()->kepala()->create();
    $menu = Menu::factory()->create();

    $this->actingAs($kepala)->get(route('admin.menu.index'))->assertOk();
    $this->actingAs($kepala)->get(route('admin.menu.show', $menu))->assertOk();
    $this->actingAs($kepala)->get(route('admin.menu.create'))->assertForbidden();
    $this->actingAs($kepala)->post(route('admin.menu.store'), menuData())->assertForbidden();
    $this->actingAs($kepala)->get(route('admin.menu.edit', $menu))->assertForbidden();
    $this->actingAs($kepala)->put(route('admin.menu.update', $menu), menuData())->assertForbidden();
    $this->actingAs($kepala)->delete(route('admin.menu.destroy', $menu))->assertForbidden();
});

test('petugas can view menu but cannot manage it', function () {
    $petugas = User::factory()->petugas()->create();
    $menu = Menu::factory()->create();

    $this->actingAs($petugas)->get(route('admin.menu.index'))->assertOk();
    $this->actingAs($petugas)->get(route('admin.menu.show', $menu))->assertOk();
    $this->actingAs($petugas)->get(route('admin.menu.create'))->assertForbidden();
    $this->actingAs($petugas)->post(route('admin.menu.store'), menuData())->assertForbidden();
    $this->actingAs($petugas)->put(route('admin.menu.update', $menu), menuData())->assertForbidden();
    $this->actingAs($petugas)->delete(route('admin.menu.destroy', $menu))->assertForbidden();
});

test('guest is redirected to login from menu module', function () {
    $this->get(route('admin.menu.index'))->assertRedirect(route('login'));
    $this->post(route('admin.menu.store'), menuData())->assertRedirect(route('login'));
});
