<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vpn_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->string('country');
            $table->string('country_code', 5);
            $table->string('city')->nullable();
            $table->integer('ping_ms')->default(0);      // Latency in ms
            $table->integer('speed_mbps')->default(0);   // Max bandwidth capacity
            $table->integer('bandwidth_used_gb')->default(0); // Current traffic
            $table->enum('status', ['online', 'offline', 'maintenance'])->default('online');
            $table->boolean('is_premium')->default(false);
            $table->text('ovpn_config')->nullable();    // OpenVPN config file string
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpn_servers');
    }
};