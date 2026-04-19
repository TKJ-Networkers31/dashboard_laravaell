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
        Schema::create('device_state', function (Blueprint $table) {
            $table->id();
            $table->string('device')->unique();
            $table->string('IP');
            $table->boolean('mode_auto');
            $table->boolean('locked');
            $table->boolean('login');
            $table->string('user');
            $table->string('UID');
            $table->boolean('pintu');
            $table->boolean('lampu1_2');
            $table->boolean('lampu3_4');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_state');
    }
};
