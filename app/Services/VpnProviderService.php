<?php

namespace App\Services;

use App\Models\VpnServer;
use App\Models\VpnServerConfig;
use App\Services\VpnSources\VpnSourceManager;

class VpnProviderService
{
    public function __construct(
        private VpnSourceManager $sourceManager
    ) {}

    /**
     * Sync VPN servers from all registered sources.
     *
     * @return int Number of servers updated/created
     */
    public function syncExternalServers(): int
    {
        $allServers = $this->sourceManager->fetchAll();
        $count = $this->persistServers($allServers);

        // Record last sync time
        \Illuminate\Support\Facades\Cache::put('vpn_last_synced_at', now(), now()->addDay());

        return $count;
    }

    /**
     * Fetch & sync servers from a single source URL in real-time.
     *
     * @return int Number of servers updated/created
     */
    public function syncFromUrl(string $url): int
    {
        $servers = $this->sourceManager->fetchSourceByUrl($url);
        $count = $this->persistServers($servers);

        // Refresh last sync time
        \Illuminate\Support\Facades\Cache::put('vpn_last_synced_at', now(), now()->addDay());

        return $count;
    }

    /**
     * Persist normalized server data into the database.
     *
     * @param array $allServers Normalized server records
     * @return int Number of servers updated/created
     */
    private function persistServers(array $allServers): int
    {
        $count = 0;

        foreach ($allServers as $serverData) {
            // Update or create the VPN server record
            $server = VpnServer::updateOrCreate(
                ['ip_address' => $serverData['ip_address']],
                [
                    'name' => $serverData['name'],
                    'country' => $serverData['country'],
                    'country_code' => $serverData['country_code'],
                    'city' => $serverData['city'] ?? 'Primary Data Center',
                    'ping_ms' => $serverData['ping_ms'] ?? rand(20, 120),
                    'speed_mbps' => $serverData['speed_mbps'] ?? rand(50, 500),
                    'bandwidth_used_gb' => $serverData['bandwidth_used_gb'] ?? rand(10, 350),
                    'status' => $serverData['status'] ?? 'online',
                    'is_premium' => $serverData['is_premium'] ?? false,
                    'source' => $serverData['source'] ?? 'unknown',
                ]
            );

            // Sync multi-protocol configs
            if (!empty($serverData['configs'])) {
                foreach ($serverData['configs'] as $configData) {
                    VpnServerConfig::updateOrCreate(
                        [
                            'vpn_server_id' => $server->id,
                            'protocol' => $configData['protocol'],
                        ],
                        [
                            'config_data' => $configData['config_data'],
                            'config_type' => $configData['config_type'] ?? 'text',
                            'remote_port' => $configData['remote_port'] ?? null,
                        ]
                    );
                }
            }

            $count++;
        }

        return $count;
    }
}

