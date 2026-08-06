<?php

namespace App\Http\Controllers;

use App\Models\VpnServer;
use App\Models\VpnServerConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VpnServerController extends Controller
{
    /**
     * Download a VPN config for a specific protocol.
     */
    public function downloadConfig(VpnServer $server, string $protocol = 'ovpn')
    {
        $user = Auth::user();

        if ($server->is_premium && !$user->hasActiveSubscription()) {
            return redirect()->route('subscription.checkout')
                ->with('error', 'Upgrade to Premium to access this location.');
        }

        // Try to find the specific protocol config
        $config = VpnServerConfig::where('vpn_server_id', $server->id)
            ->where('protocol', $protocol)
            ->first();

        if ($config) {
            $configData = $config->config_data;
            $contentType = $this->getContentType($protocol);
            $extension = $this->getFileExtension($protocol);

            return response($configData)
                ->header('Content-Type', $contentType)
                ->header('Content-Disposition', "attachment; filename=\"gzprivatevpn-{$server->country_code}.{$extension}\"");
        }

        // Fallback to legacy ovpn_config field
        if ($protocol === 'ovpn' && $server->ovpn_config) {
            return response($server->ovpn_config)
                ->header('Content-Type', 'application/x-openvpn-profile')
                ->header('Content-Disposition', "attachment; filename=\"gzprivatevpn-{$server->country_code}.ovpn\"");
        }

        // Generate a basic config as last resort
        $configData = $this->generateBasicConfig($server, $protocol);

        return response($configData)
            ->header('Content-Type', $this->getContentType($protocol))
            ->header('Content-Disposition', "attachment; filename=\"gzprivatevpn-{$server->country_code}.{$this->getFileExtension($protocol)}\"");
    }

    public function ping(VpnServer $server)
    {
        // Simulated real-time ping check
        $simulatedLatency = max(10, $server->ping_ms + rand(-5, 12));
        
        return response()->json([
            'id' => $server->id,
            'ping_ms' => $simulatedLatency,
            'status' => 'online'
        ]);
    }

    private function getContentType(string $protocol): string
    {
        return match ($protocol) {
            'v2ray' => 'application/json',
            'wireguard' => 'application/x-wireguard-profile',
            'shadowsocks' => 'application/json',
            default => 'application/x-openvpn-profile',
        };
    }

    private function getFileExtension(string $protocol): string
    {
        return match ($protocol) {
            'v2ray' => 'json',
            'wireguard' => 'conf',
            'shadowsocks' => 'json',
            default => 'ovpn',
        };
    }

    private function generateBasicConfig(VpnServer $server, string $protocol): string
    {
        return match ($protocol) {
            'v2ray' => json_encode([
                'v' => '2',
                'ps' => $server->name,
                'add' => $server->ip_address,
                'port' => '443',
                'id' => '',
                'aid' => '0',
                'net' => 'tcp',
                'type' => 'none',
                'host' => '',
                'path' => '/',
                'tls' => '',
            ]),
            'wireguard' => "[Interface]\nPrivateKey = \nAddress = \nDNS = 1.1.1.1\n\n[Peer]\nPublicKey = \nEndpoint = {$server->ip_address}:51820\nAllowedIPs = 0.0.0.0/0\nPersistentKeepalive = 25",
            'shadowsocks' => json_encode([
                'server' => $server->ip_address,
                'server_port' => 443,
                'password' => '',
                'method' => 'aes-256-gcm',
                'remarks' => $server->name,
            ]),
            default => "# GZPrivateVPN Configuration for {$server->name}\nclient\ndev tun\nproto udp\nremote {$server->ip_address} 1194\nresolv-retry infinite\nnobind\npersist-key\npersist-tun\nverb 3",
        };
    }
}

