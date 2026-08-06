<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpnServerConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'vpn_server_id',
        'protocol',
        'config_data',
        'config_type',
        'remote_port',
    ];

    protected $casts = [
        'remote_port' => 'integer',
    ];

    /**
     * Get the VPN server that owns this config.
     */
    public function vpnServer(): BelongsTo
    {
        return $this->belongsTo(VpnServer::class);
    }
}

