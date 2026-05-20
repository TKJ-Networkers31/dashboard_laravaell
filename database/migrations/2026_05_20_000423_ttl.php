<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_state', function (Blueprint $table) {
            // Simpan kapan terakhir ESP32 kirim data (TTL atau sensor)
            // Null = belum pernah konek sama sekali
            $table->timestamp('last_seen')->nullable()->after('lampu3_4');
        });
    }

    public function down(): void
    {
        Schema::table('device_state', function (Blueprint $table) {
            $table->dropColumn('last_seen');
        });
    }
};