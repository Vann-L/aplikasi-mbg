<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('sekolah')->where('status', 'aktif')->update(['status' => 'active']);
        DB::table('sekolah')->where('status', 'nonaktif')->update(['status' => 'inactive']);

        Schema::table('sekolah', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sekolah')->where('status', 'active')->update(['status' => 'aktif']);
        DB::table('sekolah')->where('status', 'inactive')->update(['status' => 'nonaktif']);

        Schema::table('sekolah', function (Blueprint $table) {
            $table->string('status')->default('aktif')->change();
        });
    }
};
