<?php

namespace App\Services\VpnSources;

interface VpnSourceInterface
{
    /**
     * Fetch and return normalized VPN server data from this source.
     *
     * Expected return format:
     * [
     *   [
     *     'name' => string,
     *     'ip_address' => string,
     *     'country' => string,
     *     'country_code' => string,
     *     'city' => string|null,
     *     'ping_ms' => int,
     *     'speed_mbps' => int,
     *     'bandwidth_used_gb' => int,
     *     'status' => 'online'|'offline'|'maintenance',
     *     'is_premium' => bool,
     *     'source' => string, // identifier for this source
     *     'configs' => [
     *       [
     *         'protocol' => string, // ovpn, v2ray, wireguard, shadowsocks, etc.
     *         'config_data' => string,
     *         'config_type' => 'text'|'base64'|'json',
     *         'remote_port' => string|int|null,
     *       ],
     *       ...
     *     ],
     *   ],
     *   ...
     * ]
     *
     * @return array
     */
    public function fetch(): array;

    /**
     * Get the display name for this source.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the unique identifier for this source.
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Set the endpoint URL (from sources.json) this source should fetch from.
     */
    public function setUrl(string $url): void;

    /**
     * Get the currently configured endpoint URL.
     */
    public function getUrl(): string;

    /**
     * Determine whether this source implementation can handle the given URL.
     */
    public function handlesUrl(string $url): bool;
}

