<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpnServer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'country',
        'country_code',
        'city',
        'ping_ms',
        'speed_mbps',
        'bandwidth_used_gb',
        'status',
        'is_premium',
        'ovpn_config',
        'source',
    ];

    protected $casts = [
        'is_premium' => 'boolean',
        'ping_ms' => 'integer',
        'speed_mbps' => 'integer',
    ];

    /**
     * Get the protocol configs for this server.
     */
    public function configs()
    {
        return $this->hasMany(VpnServerConfig::class);
    }

    /**
     * Get the list of available protocols for this server.
     *
     * @return array<string, int> Protocol name => VpnServerConfig id
     */
    public function protocolsAvailable(): array
    {
        return $this->configs()
            ->pluck('id', 'protocol')
            ->toArray();
    }
}

