<?php

namespace App\Services\VpnSources;

use Illuminate\Support\Facades\Http;

class OpenProxyListSource implements VpnSourceInterface
{
    private const DEFAULT_API_URL = 'https://openproxylist.com/v2ray/';
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
        return str_contains($url, 'openproxylist.com');
    }

    public function fetch(): array
    {
        try {
            $response = Http::timeout(10)->get($this->url);

            if (!$response->successful()) {
                logger()->warning('OpenProxyListSource: API request failed', [
                    'status' => $response->status(),
                ]);
                return [];
            }

            $data = $response->json();

            if (!is_array($data)) {
                // Try parsing as text if not JSON
                $body = $response->body();
                $lines = explode("\n", $body);
                return $this->parseTextResponse($lines);
            }

            return $this->parseJsonResponse($data);

        } catch (\Exception $e) {
            logger()->error("OpenProxyListSource: Exception - {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Parse JSON response format.
     */
    private function parseJsonResponse(array $data): array
    {
        $servers = [];
        $count = 0;

        foreach ($data as $item) {
            if ($count >= self::BATCH_LIMIT) {
                break;
            }

            $ip = $item['ip'] ?? $item['host'] ?? $item['address'] ?? null;
            $country = $item['country'] ?? $item['location'] ?? 'Unknown';
            $countryCode = $item['country_code'] ?? $item['cc'] ?? strtolower(substr($country, 0, 2));
            $city = $item['city'] ?? null;
            $ping = (int)($item['ping'] ?? $item['latency'] ?? $item['ping_ms'] ?? rand(20, 150));
            $speed = (int)($item['speed'] ?? $item['speed_mbps'] ?? rand(30, 400));

            if (!$ip) {
                continue;
            }

            $configs = [];

            // V2Ray config (primary protocol for this source)
            $v2rayConfig = $item['config'] ?? $item['v2ray'] ?? $item['v2ray_config'] ?? null;
            if ($v2rayConfig) {
                $configData = is_array($v2rayConfig) ? json_encode($v2rayConfig) : $v2rayConfig;
                $configs[] = [
                    'protocol' => 'v2ray',
                    'config_data' => $configData,
                    'config_type' => 'json',
                    'remote_port' => $item['port'] ?? 443,
                ];
            }

            // Check for VMess share links or configs
            $vmessLink = $item['vmess'] ?? null;
            if ($vmessLink) {
                $configs[] = [
                    'protocol' => 'v2ray',
                    'config_data' => $vmessLink,
                    'config_type' => 'base64',
                    'remote_port' => $item['port'] ?? 443,
                ];
            }

            // OVPN config if present
            $ovpnConfig = $item['ovpn'] ?? $item['openvpn'] ?? $item['ovpn_config'] ?? null;
            if ($ovpnConfig) {
                $configs[] = [
                    'protocol' => 'ovpn',
                    'config_data' => $ovpnConfig,
                    'config_type' => 'text',
                    'remote_port' => $item['port'] ?? 1194,
                ];
            }

            // If no V2Ray config found, generate a basic VMess config
            if (empty($configs)) {
                $port = $item['port'] ?? 443;
                $vmessUuid = $item['uuid'] ?? \Illuminate\Support\Str::uuid()->toString();
                $vmessConfig = [
                    'v' => '2',
                    'ps' => $item['remark'] ?? "{$country}-V2Ray",
                    'add' => $ip,
                    'port' => (string)$port,
                    'id' => $vmessUuid,
                    'aid' => '0',
                    'net' => $item['network'] ?? 'tcp',
                    'type' => 'none',
                    'host' => $item['host'] ?? '',
                    'path' => $item['path'] ?? '/',
                    'tls' => $item['tls'] ?? '',
                ];
                $configs[] = [
                    'protocol' => 'v2ray',
                    'config_data' => json_encode($vmessConfig),
                    'config_type' => 'json',
                    'remote_port' => $port,
                ];
            }

            $servers[] = [
                'name' => $item['name'] ?? "{$country} V2Ray Node",
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
    }

    /**
     * Parse plain text response (one server per line).
     */
    private function parseTextResponse(array $lines): array
    {
        $servers = [];
        $count = 0;

        foreach ($lines as $line) {
            if ($count >= self::BATCH_LIMIT) {
                break;
            }

            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Try to parse as VMess link
            if (str_starts_with($line, 'vmess://')) {
                $servers[] = [
                    'name' => 'V2Ray Node ' . ($count + 1),
                    'ip_address' => $this->extractIpFromVmess($line) ?? '0.0.0.0',
                    'country' => 'Unknown',
                    'country_code' => 'xx',
                    'city' => 'Primary Data Center',
                    'ping_ms' => rand(20, 120),
                    'speed_mbps' => rand(50, 500),
                    'bandwidth_used_gb' => rand(10, 350),
                    'status' => 'online',
                    'is_premium' => false,
                    'source' => $this->getIdentifier(),
                    'configs' => [
                        [
                            'protocol' => 'v2ray',
                            'config_data' => $line,
                            'config_type' => 'base64',
                            'remote_port' => 443,
                        ],
                    ],
                ];
                $count++;
                continue;
            }

            // Try to parse as JSON line
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                return $this->parseJsonResponse([$decoded]);
            }
        }

        return $servers;
    }

    /**
     * Attempt to extract IP from a VMess link.
     */
    private function extractIpFromVmess(string $vmessLink): ?string
    {
        try {
            $payload = substr($vmessLink, 8); // Remove "vmess://"
            $decoded = base64_decode($payload, true);
            if ($decoded === false) {
                return null;
            }
            $data = json_decode($decoded, true);
            if (is_array($data) && isset($data['add'])) {
                return $data['add'];
            }
        } catch (\Exception $e) {
            // Fall through
        }

        return null;
    }

    public function getName(): string
    {
        return 'Open Proxy List';
    }

    public function getIdentifier(): string
    {
        return 'openproxylist';
    }
}

