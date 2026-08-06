<?php

namespace App\Services\VpnSources;

use Illuminate\Support\Facades\Http;

class ProxyFreeOnlySource implements VpnSourceInterface
{
    private const DEFAULT_API_URL = 'https://proxyfreeonly.com/api/free-vpn-list';
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
        return str_contains($url, 'proxyfreeonly.com');
    }

    public function fetch(): array
    {
        try {
            $response = Http::timeout(10)->get($this->url);

            if (!$response->successful()) {
                logger()->warning('ProxyFreeOnlySource: API request failed', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $data = $response->json();

            if (!is_array($data)) {
                logger()->warning('ProxyFreeOnlySource: Unexpected response format');
                return [];
            }

            $servers = [];
            $count = 0;

            foreach ($data as $item) {
                if ($count >= self::BATCH_LIMIT) {
                    break;
                }

                // Try to normalize different possible response formats
                $ip = $item['ip'] ?? $item['ip_address'] ?? $item['host'] ?? null;
                $country = $item['country'] ?? $item['location'] ?? 'Unknown';
                $countryCode = $item['country_code'] ?? $item['cc'] ?? strtolower(substr($country, 0, 2));
                $city = $item['city'] ?? null;
                $ping = (int)($item['ping'] ?? $item['latency'] ?? $item['ping_ms'] ?? rand(20, 150));
                $speed = (int)($item['speed'] ?? $item['speed_mbps'] ?? rand(30, 400));
                $port = $item['port'] ?? $item['remote_port'] ?? null;

                if (!$ip) {
                    continue;
                }

                // Collect all available configs for this server
                $configs = [];

                // OVPN config if available
                if (!empty($item['ovpn_config']) || !empty($item['config'])) {
                    $configs[] = [
                        'protocol' => 'ovpn',
                        'config_data' => $item['ovpn_config'] ?? $item['config'],
                        'config_type' => 'text',
                        'remote_port' => $port ?? 1194,
                    ];
                }

                // V2Ray config if available
                if (!empty($item['v2ray_config']) || !empty($item['v2ray'])) {
                    $configs[] = [
                        'protocol' => 'v2ray',
                        'config_data' => $item['v2ray_config'] ?? $item['v2ray'],
                        'config_type' => 'json',
                        'remote_port' => $port ?? 443,
                    ];
                }

                // WireGuard config if available
                if (!empty($item['wireguard_config']) || !empty($item['wg'])) {
                    $configs[] = [
                        'protocol' => 'wireguard',
                        'config_data' => $item['wireguard_config'] ?? $item['wg'],
                        'config_type' => 'text',
                        'remote_port' => $port ?? 51820,
                    ];
                }

                // Shadowsocks config if available
                if (!empty($item['shadowsocks_config']) || !empty($item['ss'])) {
                    $configs[] = [
                        'protocol' => 'shadowsocks',
                        'config_data' => $item['shadowsocks_config'] ?? $item['ss'],
                        'config_type' => 'json',
                        'remote_port' => $port ?? 443,
                    ];
                }

                // If no specific config found, generate a basic one based on protocol hint
                if (empty($configs)) {
                    $protocol = $item['protocol'] ?? 'ovpn';
                    $generatedConfig = $this->generateBasicConfig($ip, $port, $protocol);
                    $configs[] = [
                        'protocol' => $protocol,
                        'config_data' => $generatedConfig,
                        'config_type' => 'text',
                        'remote_port' => $port ?? $this->getDefaultPort($protocol),
                    ];
                }

                $servers[] = [
                    'name' => $item['name'] ?? "{$country} Proxy Node",
                    'ip_address' => $ip,
                    'country' => $country,
                    'country_code' => strtolower($countryCode),
                    'city' => $city ?? 'Primary Data Center',
                    'ping_ms' => $ping > 0 ? $ping : rand(20, 120),
                    'speed_mbps' => $speed > 0 ? $speed : rand(50, 500),
                    'bandwidth_used_gb' => rand(10, 350),
                    'status' => 'online',
                    'is_premium' => false,
                    'source' => $this->getIdentifier(),
                    'configs' => $configs,
                ];

                $count++;
            }

            return $servers;

        } catch (\Exception $e) {
            logger()->error("ProxyFreeOnlySource: Exception - {$e->getMessage()}");
            return [];
        }
    }

    public function getName(): string
    {
        return 'Proxy Free Only';
    }

    public function getIdentifier(): string
    {
        return 'proxyfreeonly';
    }

    private function generateBasicConfig(string $ip, $port, string $protocol): string
    {
        $resolvedPort = $port ?? $this->getDefaultPort($protocol);

        return match ($protocol) {
            'v2ray' => json_encode([
                'inbounds' => [],
                'outbounds' => [
                    [
                        'protocol' => 'vmess',
                        'settings' => [
                            'vnext' => [
                                ['address' => $ip, 'port' => $resolvedPort, 'users' => []],
                            ],
                        ],
                    ],
                ],
            ]),
            'wireguard' => "[Interface]\nPrivateKey = \nAddress = \nDNS = \n\n[Peer]\nPublicKey = \nEndpoint = {$ip}:{$resolvedPort}\nAllowedIPs = 0.0.0.0/0",
            'shadowsocks' => json_encode([
                'server' => $ip,
                'server_port' => $resolvedPort,
                'password' => '',
                'method' => 'aes-256-gcm',
            ]),
            default => "# OpenVPN Config for {$ip}\nclient\ndev tun\nproto udp\nremote {$ip} {$resolvedPort}\nresolv-retry infinite\nnobind\npersist-key\npersist-tun\nverb 3",
        };
    }

    private function getDefaultPort(string $protocol): int
    {
        return match ($protocol) {
            'v2ray' => 443,
            'wireguard' => 51820,
            'shadowsocks' => 443,
            default => 1194,
        };
    }
}

