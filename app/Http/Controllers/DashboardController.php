<?php

namespace App\Http\Controllers;

use App\Models\VpnServer;
use App\Models\VpnServerConfig;
use App\Services\VpnProviderService;
use App\Services\VpnSources\VpnSourceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request, VpnSourceManager $sourceManager)
    {
        $query = VpnServer::where('status', '=', 'online', 'and');

        // Search by name, country, or city
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('country', 'ilike', "%{$search}%")
                  ->orWhere('city', 'ilike', "%{$search}%");
            });
        }

        // Location Filter
        if ($request->filled('country')) {
            $query->where('country_code', strtolower($request->input('country')));
        }

        // Source Filter
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        // Protocol Filter - filter servers that have a specific protocol config
        if ($request->filled('protocol')) {
            $protocol = $request->input('protocol');
            $serverIdsWithProtocol = VpnServerConfig::where('protocol', $protocol)
                ->pluck('vpn_server_id');
            $query->whereIn('id', $serverIdsWithProtocol);
        }

        // Sorting (speed, ping)
        if ($request->input('sort') === 'speed') {
            $query->orderBy('speed_mbps', 'desc');
        } elseif ($request->input('sort') === 'ping') {
            $query->orderBy('ping_ms', 'asc');
        } else {
            $query->orderBy('is_premium', 'desc');
        }

        $servers = $query->with('configs')->get();
        $countries = VpnServer::select('country', 'country_code')
            ->groupBy('country', 'country_code')
            ->get();

        // Get all available protocols from the configs table
        $protocols = VpnServerConfig::select('protocol')
            ->distinct()
            ->pluck('protocol')
            ->toArray();

        // Get all available sources
        $sources = VpnServer::select('source')
            ->whereNotNull('source')
            ->distinct()
            ->pluck('source')
            ->toArray();
        $sourceDisplayNames = $sourceManager->getDisplayNames();

        // Last sync time for the real-time status indicator
        $lastSyncedAt = Cache::get('vpn_last_synced_at');

        // Source URLs currently configured in sources.json
        $sourceUrls = $this->loadSourceUrls();

        return view('dashboard', compact(
            'servers',
            'countries',
            'protocols',
            'sources',
            'sourceDisplayNames',
            'lastSyncedAt',
            'sourceUrls'
        ));
    }

    /**
     * Real-time refresh: fetch servers & configs from all sources.json URLs
     * and sync them into the database.
     */
    public function refresh(Request $request, VpnProviderService $service)
    {
        $urls = $this->loadSourceUrls();

        $total = 0;
        $results = [];

        foreach ($urls as $url) {
            try {
                $count = $service->syncFromUrl($url);
                $total += $count;
                $results[] = [
                    'url' => $url,
                    'status' => 'ok',
                    'servers' => $count,
                ];
            } catch (\Exception $e) {
                logger()->error("Dashboard refresh failed for {$url}: {$e->getMessage()}");
                $results[] = [
                    'url' => $url,
                    'status' => 'error',
                    'servers' => 0,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'total_servers_synced' => $total,
            'last_synced_at' => now()->toIso8601String(),
            'results' => $results,
        ]);
    }

    /**
     * Load source URLs from sources.json.
     *
     * @return array<int, string>
     */
    private function loadSourceUrls(): array
    {
        $path = base_path('sources.json');
        if (!file_exists($path)) {
            return [];
        }

        $data = json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            return [];
        }

        // Accept a { "urls": [...] } wrapper
        if (isset($data['urls']) && is_array($data['urls'])) {
            $data = $data['urls'];
        }

        // Normalize each entry: plain string URL or object with a "url" key
        $urls = [];
        foreach ($data as $entry) {
            if (is_string($entry)) {
                $urls[] = $entry;
            } elseif (is_array($entry) && isset($entry['url']) && is_string($entry['url'])) {
                $urls[] = $entry['url'];
            }
        }

        return array_values(array_filter($urls, fn ($u) => filter_var($u, FILTER_VALIDATE_URL)));
    }
}

