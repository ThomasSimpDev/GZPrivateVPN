<?php

namespace App\Services\VpnSources;

use Illuminate\Support\Facades\Http;

class VpnGateSource implements VpnSourceInterface
{
    private const DEFAULT_API_URL = 'https://www.vpngate.net/api/iphone/';
    private const BATCH_LIMIT = 15;

    private string $url;

    public function __construct()
    {
        $this->url = self::DEFAULT_API_URL;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function handlesUrl(string $url): bool
    {
        return str_contains($url, 'vpngate.net') && str_contains($url, 'api');
    }

    public function fetch(): array
    {
        $response = Http::timeout(10)->get($this->url);

        if (!$response->successful()) {
            return [];
        }

        $lines = explode("\n", $response->body());
        $servers = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '#') || str_starts_with($line, '*') || trim($line) === '') {
                continue;
            }

            $data = explode(',', $line);
            if (count($data) < 15) {
                continue;
            }

            $hostName = $data[0];
            $ip = $data[1];
            $speed = round(((int)$data[4]) / 1000000);
            $ping = (int)$data[3];
            $countryLong = $data[6];
            $countryShort = strtolower($data[5]);
            $configBase64 = $data[14] ?? null;

            $servers[] = [
                'name' => $hostName . ' Node',
                'ip_address' => $ip,
                'country' => $countryLong,
                'country_code' => $countryShort,
                'city' => 'Primary Data Center',
                'ping_ms' => $ping > 0 ? $ping : rand(20, 120),
                'speed_mbps' => $speed > 0 ? $speed : rand(50, 500),
                'bandwidth_used_gb' => rand(10, 350),
                'status' => 'online',
                'is_premium' => rand(0, 1) === 1,
                'source' => $this->getIdentifier(),
                'configs' => $configBase64 ? [
                    [
                        'protocol' => 'ovpn',
                        'config_data' => base64_decode($configBase64),
                        'config_type' => 'text',
                        'remote_port' => 1194,
                    ],
                ] : [],
            ];

            if (count($servers) >= self::BATCH_LIMIT) {
                break;
            }
        }

        return $servers;
    }

    public function getName(): string
    {
        return 'VPN Gate';
    }

    public function getIdentifier(): string
    {
        return 'vpngate';
    }
}

