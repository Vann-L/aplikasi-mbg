<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('distribusi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_distribusi')->unique();
            $table->foreignId('produksi_id')->constrained('produksi')->restrictOnDelete();
            $table->foreignId('sekolah_id')->constrained('sekolah')->restrictOnDelete();
            $table->date('tanggal');
            $table->unsignedInteger('jumlah_porsi');
            $table->foreignId('petugas_id')->nullable()->constrained('pegawai')->nullOnDelete();
            $table->string('kendaraan')->nullable();
            $table->timestamp('jam_berangkat')->nullable();
            $table->string('status')->default('dijadwalkan');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribusi');
    }
};
