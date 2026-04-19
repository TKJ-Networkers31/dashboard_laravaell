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
        Schema::create('log_sensor', function (Blueprint $table) {
            $table->id();
            $table->string('device');
            $table->string('rssi');
            $table->float('suhu');
            $table->float('kelembapan');
            $table->integer('cahaya');
            $table->integer('jarak_objek');
            $table->integer('sisa_memori');
            $table->integer('max_size_memori');
            $table->timestamp('record_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_sensor');
    }
};
