<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\Admin\BahanBakuController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PegawaiController;
use App\Http\Controllers\Admin\ResetUserPasswordController;
use App\Http\Controllers\Admin\SekolahController;
use App\Http\Controllers\Admin\StokController as AdminStokController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DistribusiController;
use App\Http\Controllers\Kepala\AbsensiController as KepalaAbsensiController;
use App\Http\Controllers\Kepala\DashboardController as KepalaDashboardController;
use App\Http\Controllers\Kepala\DistribusiController as KepalaDistribusiController;
use App\Http\Controllers\Kepala\PenerimaanController as KepalaPenerimaanController;
use App\Http\Controllers\Kepala\ProduksiController as KepalaProduksiController;
use App\Http\Controllers\Kepala\StokController as KepalaStokController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PenerimaanController;
use App\Http\Controllers\Petugas\AbsensiController as PetugasAbsensiController;
use App\Http\Controllers\Petugas\DashboardController as PetugasDashboardController;
use App\Http\Controllers\Petugas\StokController as PetugasStokController;
use App\Http\Controllers\PlaceholderController;
use App\Http\Controllers\ProduksiController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    $user = $request->user();

    if ($user instanceof User) {
        return redirect()->to(AuthenticatedSessionController::homeRouteFor($user));
    }

    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/stok', [AdminStokController::class, 'index'])->name('stok.index');
    Route::get('/stok/mutasi', [AdminStokController::class, 'history'])->name('stok.history');
    Route::get('/stok/mutasi/tambah', [AdminStokController::class, 'create'])->name('stok.create');
    Route::post('/stok/mutasi', [AdminStokController::class, 'store'])->name('stok.store');

    Route::get('/distribusi', [DistribusiController::class, 'index'])->name('distribusi.index');
    Route::get('/distribusi/{distribusi}', [DistribusiController::class, 'show'])->whereNumber('distribusi')->name('distribusi.show');
    Route::get('/distribusi/{distribusi}/edit', [DistribusiController::class, 'edit'])->whereNumber('distribusi')->name('distribusi.edit');
    Route::put('/distribusi/{distribusi}', [DistribusiController::class, 'update'])->whereNumber('distribusi')->name('distribusi.update');
    Route::post('/distribusi/{distribusi}/siapkan', [DistribusiController::class, 'prepare'])->whereNumber('distribusi')->name('distribusi.siapkan');
    Route::post('/distribusi/{distribusi}/kirim', [DistribusiController::class, 'send'])->whereNumber('distribusi')->name('distribusi.kirim');
    Route::delete('/distribusi/{distribusi}', [DistribusiController::class, 'destroy'])->whereNumber('distribusi')->name('distribusi.destroy');

    Route::get('/penerimaan', [PenerimaanController::class, 'index'])->name('penerimaan.index');
    Route::get('/penerimaan/{distribusi}', [PenerimaanController::class, 'show'])->whereNumber('distribusi')->name('penerimaan.show');
    Route::get('/penerimaan/{distribusi}/catat', [PenerimaanController::class, 'create'])->whereNumber('distribusi')->name('penerimaan.create');
    Route::post('/penerimaan/{distribusi}', [PenerimaanController::class, 'store'])->whereNumber('distribusi')->name('penerimaan.store');
    Route::post('/penerimaan/{distribusi}/selesai', [PenerimaanController::class, 'selesai'])->whereNumber('distribusi')->name('penerimaan.selesai');

    Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/qr', [AbsensiController::class, 'qr'])->name('absensi.qr');
    Route::get('/absensi/qr/token', [AbsensiController::class, 'token'])->name('absensi.token');
    Route::get('/absensi/qr/img', [AbsensiController::class, 'qrImg'])->name('absensi.qr-img');
    Route::get('/laporan', [LaporanController::class, 'produksi'])->name('laporan.index');
    Route::get('/laporan/produksi', [LaporanController::class, 'produksi'])->name('laporan.produksi');
    Route::get('/laporan/stok', [LaporanController::class, 'stok'])->name('laporan.stok');
    Route::get('/laporan/distribusi', [LaporanController::class, 'distribusi'])->name('laporan.distribusi');
    Route::get('/laporan/penerimaan', [LaporanController::class, 'penerimaan'])->name('laporan.penerimaan');
    Route::get('/laporan/absensi', [LaporanController::class, 'absensi'])->name('laporan.absensi');
    Route::get('/users', PlaceholderController::class)->defaults('label', 'User')->name('user.index');

    Route::post('/users/{user}/reset-password', ResetUserPasswordController::class)->name('users.reset-password');
});

Route::middleware(['auth', 'role:admin,kepala'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/pegawai', [PegawaiController::class, 'index'])->name('pegawai.index');
    Route::get('/pegawai/{pegawai}', [PegawaiController::class, 'show'])->whereNumber('pegawai')->name('pegawai.show');
});

Route::middleware(['auth', 'role:admin,kepala,petugas'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/sekolah', [SekolahController::class, 'index'])->name('sekolah.index');
    Route::get('/sekolah/{sekolah}', [SekolahController::class, 'show'])->whereNumber('sekolah')->name('sekolah.show');

    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menu/{menu}', [MenuController::class, 'show'])->whereNumber('menu')->name('menu.show');

    Route::get('/bahan-baku', [BahanBakuController::class, 'index'])->name('bahan-baku.index');
    Route::get('/bahan-baku/{bahan_baku}', [BahanBakuController::class, 'show'])->whereNumber('bahan_baku')->name('bahan-baku.show');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/pegawai/create', [PegawaiController::class, 'create'])->name('pegawai.create');
    Route::post('/pegawai', [PegawaiController::class, 'store'])->name('pegawai.store');
    Route::get('/pegawai/{pegawai}/edit', [PegawaiController::class, 'edit'])->whereNumber('pegawai')->name('pegawai.edit');
    Route::put('/pegawai/{pegawai}', [PegawaiController::class, 'update'])->whereNumber('pegawai')->name('pegawai.update');
    Route::delete('/pegawai/{pegawai}', [PegawaiController::class, 'destroy'])->whereNumber('pegawai')->name('pegawai.destroy');

    Route::get('/sekolah/create', [SekolahController::class, 'create'])->name('sekolah.create');
    Route::post('/sekolah', [SekolahController::class, 'store'])->name('sekolah.store');
    Route::get('/sekolah/{sekolah}/edit', [SekolahController::class, 'edit'])->whereNumber('sekolah')->name('sekolah.edit');
    Route::put('/sekolah/{sekolah}', [SekolahController::class, 'update'])->whereNumber('sekolah')->name('sekolah.update');
    Route::delete('/sekolah/{sekolah}', [SekolahController::class, 'destroy'])->whereNumber('sekolah')->name('sekolah.destroy');

    Route::get('/menu/create', [MenuController::class, 'create'])->name('menu.create');
    Route::post('/menu', [MenuController::class, 'store'])->name('menu.store');
    Route::get('/menu/{menu}/edit', [MenuController::class, 'edit'])->whereNumber('menu')->name('menu.edit');
    Route::put('/menu/{menu}', [MenuController::class, 'update'])->whereNumber('menu')->name('menu.update');
    Route::delete('/menu/{menu}', [MenuController::class, 'destroy'])->whereNumber('menu')->name('menu.destroy');

    Route::get('/bahan-baku/create', [BahanBakuController::class, 'create'])->name('bahan-baku.create');
    Route::post('/bahan-baku', [BahanBakuController::class, 'store'])->name('bahan-baku.store');
    Route::get('/bahan-baku/{bahan_baku}/edit', [BahanBakuController::class, 'edit'])->whereNumber('bahan_baku')->name('bahan-baku.edit');
    Route::put('/bahan-baku/{bahan_baku}', [BahanBakuController::class, 'update'])->whereNumber('bahan_baku')->name('bahan-baku.update');
    Route::delete('/bahan-baku/{bahan_baku}', [BahanBakuController::class, 'destroy'])->whereNumber('bahan_baku')->name('bahan-baku.destroy');
});

Route::middleware(['auth', 'role:kepala'])->prefix('kepala')->name('kepala.')->group(function () {
    Route::get('/', KepalaDashboardController::class)->name('dashboard');

    Route::get('/stok', [KepalaStokController::class, 'index'])->name('stok.index');
    Route::get('/stok/mutasi', [KepalaStokController::class, 'history'])->name('stok.history');

    Route::get('/distribusi', [KepalaDistribusiController::class, 'index'])->name('distribusi.index');
    Route::get('/distribusi/{distribusi}', [KepalaDistribusiController::class, 'show'])->whereNumber('distribusi')->name('distribusi.show');

    Route::get('/penerimaan', [KepalaPenerimaanController::class, 'index'])->name('penerimaan.index');
    Route::get('/penerimaan/{distribusi}', [KepalaPenerimaanController::class, 'show'])->whereNumber('distribusi')->name('penerimaan.show');

    Route::get('/absensi', [KepalaAbsensiController::class, 'index'])->name('absensi.index');
    Route::get('/absensi/qr', [KepalaAbsensiController::class, 'qr'])->name('absensi.qr');
    Route::get('/absensi/qr/token', [KepalaAbsensiController::class, 'token'])->name('absensi.token');
    Route::get('/absensi/qr/img', [KepalaAbsensiController::class, 'qrImg'])->name('absensi.qr-img');
    Route::get('/laporan', [LaporanController::class, 'produksi'])->name('laporan.index');
    Route::get('/laporan/produksi', [LaporanController::class, 'produksi'])->name('laporan.produksi');
    Route::get('/laporan/stok', [LaporanController::class, 'stok'])->name('laporan.stok');
    Route::get('/laporan/distribusi', [LaporanController::class, 'distribusi'])->name('laporan.distribusi');
    Route::get('/laporan/penerimaan', [LaporanController::class, 'penerimaan'])->name('laporan.penerimaan');
    Route::get('/laporan/absensi', [LaporanController::class, 'absensi'])->name('laporan.absensi');
});

Route::middleware(['auth', 'role:petugas'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/', PetugasDashboardController::class)->name('dashboard');

    Route::get('/stok', [PetugasStokController::class, 'index'])->name('stok.index');
    Route::get('/stok/mutasi', [PetugasStokController::class, 'history'])->name('stok.history');
    Route::get('/stok/mutasi/tambah', [PetugasStokController::class, 'create'])->name('stok.create');
    Route::post('/stok/mutasi', [PetugasStokController::class, 'store'])->name('stok.store');

    Route::get('/jadwal', PlaceholderController::class)->defaults('label', 'Jadwal')->name('jadwal.index');
    Route::get('/absensi', [PetugasAbsensiController::class, 'index'])->name('absensi.index');
    Route::post('/absensi/scan', [PetugasAbsensiController::class, 'scan'])->name('absensi.scan');
    Route::get('/absensi/scan/{token}', [PetugasAbsensiController::class, 'scanToken'])->name('absensi.scan-token');
    Route::get('/distribusi', [DistribusiController::class, 'index'])->name('distribusi.index');
    Route::get('/distribusi/{distribusi}', [DistribusiController::class, 'show'])->whereNumber('distribusi')->name('distribusi.show');
    Route::post('/distribusi/{distribusi}/siapkan', [DistribusiController::class, 'prepare'])->whereNumber('distribusi')->name('distribusi.siapkan');
    Route::post('/distribusi/{distribusi}/kirim', [DistribusiController::class, 'send'])->whereNumber('distribusi')->name('distribusi.kirim');

    Route::get('/penerimaan', [PenerimaanController::class, 'index'])->name('penerimaan.index');
    Route::get('/penerimaan/{distribusi}', [PenerimaanController::class, 'show'])->whereNumber('distribusi')->name('penerimaan.show');
    Route::get('/penerimaan/{distribusi}/catat', [PenerimaanController::class, 'create'])->whereNumber('distribusi')->name('penerimaan.create');
    Route::post('/penerimaan/{distribusi}', [PenerimaanController::class, 'store'])->whereNumber('distribusi')->name('penerimaan.store');
    Route::post('/penerimaan/{distribusi}/selesai', [PenerimaanController::class, 'selesai'])->whereNumber('distribusi')->name('penerimaan.selesai');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.produksi.')->group(function () {
    Route::get('/produksi', [ProduksiController::class, 'index'])->name('index');
    Route::get('/produksi/create', [ProduksiController::class, 'create'])->name('create');
    Route::post('/produksi', [ProduksiController::class, 'store'])->name('store');
    Route::get('/produksi/{produksi}', [ProduksiController::class, 'show'])->whereNumber('produksi')->name('show');
    Route::delete('/produksi/{produksi}', [ProduksiController::class, 'destroy'])->whereNumber('produksi')->name('destroy');

    Route::post('/produksi/{produksi}/distribusi', [ProduksiController::class, 'storeDistribution'])->whereNumber('produksi')->name('distribution.store');
    Route::put('/produksi/{produksi}/distribusi/{distribusi}', [ProduksiController::class, 'updateDistribution'])->whereNumber('produksi')->whereNumber('distribusi')->name('distribution.update');
    Route::delete('/produksi/{produksi}/distribusi/{distribusi}', [ProduksiController::class, 'destroyDistribution'])->whereNumber('produksi')->whereNumber('distribusi')->name('distribution.destroy');

    Route::post('/produksi/{produksi}/bahan', [ProduksiController::class, 'storeIngredient'])->whereNumber('produksi')->name('ingredient.store');
    Route::delete('/produksi/{produksi}/bahan/{produksiBahan}', [ProduksiController::class, 'destroyIngredient'])->whereNumber('produksi')->whereNumber('produksiBahan')->name('ingredient.destroy');

    Route::post('/produksi/{produksi}/mulai', [ProduksiController::class, 'start'])->whereNumber('produksi')->name('start');
    Route::put('/produksi/{produksi}/status', [ProduksiController::class, 'updateStatus'])->whereNumber('produksi')->name('status.update');
    Route::put('/produksi/{produksi}/hasil', [ProduksiController::class, 'updateResult'])->whereNumber('produksi')->name('result.update');
});

Route::middleware(['auth', 'role:kepala'])->prefix('kepala')->name('kepala.produksi.')->group(function () {
    Route::get('/produksi', [KepalaProduksiController::class, 'index'])->name('index');
    Route::get('/produksi/{produksi}', [KepalaProduksiController::class, 'show'])->whereNumber('produksi')->name('show');
});

Route::middleware(['auth', 'role:petugas'])->prefix('petugas')->name('petugas.produksi.')->group(function () {
    Route::get('/produksi', [ProduksiController::class, 'index'])->name('index');
    Route::get('/produksi/create', [ProduksiController::class, 'create'])->name('create');
    Route::post('/produksi', [ProduksiController::class, 'store'])->name('store');
    Route::get('/produksi/{produksi}', [ProduksiController::class, 'show'])->whereNumber('produksi')->name('show');

    Route::post('/produksi/{produksi}/distribusi', [ProduksiController::class, 'storeDistribution'])->whereNumber('produksi')->name('distribution.store');
    Route::put('/produksi/{produksi}/distribusi/{distribusi}', [ProduksiController::class, 'updateDistribution'])->whereNumber('produksi')->whereNumber('distribusi')->name('distribution.update');
    Route::delete('/produksi/{produksi}/distribusi/{distribusi}', [ProduksiController::class, 'destroyDistribution'])->whereNumber('produksi')->whereNumber('distribusi')->name('distribution.destroy');

    Route::post('/produksi/{produksi}/bahan', [ProduksiController::class, 'storeIngredient'])->whereNumber('produksi')->name('ingredient.store');
    Route::delete('/produksi/{produksi}/bahan/{produksiBahan}', [ProduksiController::class, 'destroyIngredient'])->whereNumber('produksi')->whereNumber('produksiBahan')->name('ingredient.destroy');

    Route::post('/produksi/{produksi}/mulai', [ProduksiController::class, 'start'])->whereNumber('produksi')->name('start');
    Route::put('/produksi/{produksi}/status', [ProduksiController::class, 'updateStatus'])->whereNumber('produksi')->name('status.update');
    Route::put('/produksi/{produksi}/hasil', [ProduksiController::class, 'updateResult'])->whereNumber('produksi')->name('result.update');
});
