<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns to existing vpn_servers table
        Schema::table('vpn_servers', function (Blueprint $table) {
            $table->string('source')->nullable()->after('is_premium');
            $table->json('protocols_available')->nullable()->after('source');
        });

        // Create new vpn_server_configs table for multi-protocol configs
        Schema::create('vpn_server_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vpn_server_id')->constrained()->cascadeOnDelete();
            $table->string('protocol', 50); // ovpn, v2ray, wireguard, shadowsocks, etc.
            $table->text('config_data');
            $table->string('config_type', 50)->default('text'); // text, base64, json, etc.
            $table->string('remote_port')->nullable(); // port for the protocol
            $table->timestamps();

            // Each server can have only one config per protocol
            $table->unique(['vpn_server_id', 'protocol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vpn_server_configs');

        Schema::table('vpn_servers', function (Blueprint $table) {
            $table->dropColumn(['source', 'protocols_available']);
        });
    }
};
