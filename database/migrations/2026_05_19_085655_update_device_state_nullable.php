<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_state', function (Blueprint $table) {
            // Buat semua kolom nullable dengan default value
            // agar device baru bisa diinsert tanpa data lengkap
            $table->string('IP')->default('unknown')->change();
            $table->boolean('mode_auto')->default(false)->change();
            $table->boolean('locked')->default(false)->change();
            $table->boolean('login')->default(false)->change();
            $table->string('user')->default('none')->change();
            $table->string('UID')->default('none')->change();
            $table->boolean('pintu')->default(false)->change();
            $table->boolean('lampu1_2')->default(false)->change();
            $table->boolean('lampu3_4')->default(false)->change();
        });
    }

    public function down(): void
    {
        // tidak perlu rollback
    }
};
