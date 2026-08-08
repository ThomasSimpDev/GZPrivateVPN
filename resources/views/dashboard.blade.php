<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('GZPrivateVPN Servers') }}
            </h2>
            <div class="flex items-center gap-3">
                <!-- Last Sync + Live Indicator -->
                <div class="flex items-center gap-2 text-xs text-gray-600">
                    <span id="live-indicator" class="inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        <span id="live-label">Live</span>
                    </span>
                    <span id="last-synced-label" class="text-gray-400">
                        Last synced: {{ $lastSyncedAt ? \Carbon\Carbon::parse($lastSyncedAt)->diffForHumans() : 'Never' }}
                    </span>
                </div>

                <!-- Refresh Now Button -->
                <button id="refresh-btn" onclick="refreshServers()"
                        class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold py-2 px-4 rounded shadow transition">
                    <svg id="refresh-spinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span id="refresh-label">Refresh Now</span>
                </button>

                @if(auth()->user()->hasActiveSubscription())
                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-green-400">
                        PREMIUM ACTIVE ({{ strtoupper(auth()->user()->plan_type) }})
                    </span>
                @else
                    <a href="{{ route('subscription.checkout') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 px-4 rounded shadow">
                        Upgrade to Premium
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- System Metrics & Filters -->
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    
                    <!-- Search Field -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Search Nodes</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Country, City, Server IP..." class="w-full border-gray-300 rounded-md text-sm shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Location Picker -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Location</label>
                        <select name="country" class="w-full border-gray-300 rounded-md text-sm shadow-sm">
                            <option value="">All Locations</option>
                            @foreach($countries as $c)
                                <option value="{{ $c->country_code }}" {{ request('country') == $c->country_code ? 'selected' : '' }}>
                                    {{ $c->country }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Source Filter -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Source Provider</label>
                        <select name="source" class="w-full border-gray-300 rounded-md text-sm shadow-sm">
                            <option value="">All Sources</option>
                            @foreach($sources as $source)
                                <option value="{{ $source }}" {{ request('source') == $source ? 'selected' : '' }}>
                                    {{ $sourceDisplayNames[$source] ?? ucfirst($source) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Protocol Filter -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Protocol</label>
                        <select name="protocol" class="w-full border-gray-300 rounded-md text-sm shadow-sm">
                            <option value="">All Protocols</option>
                            @foreach($protocols as $protocol)
                                <option value="{{ $protocol }}" {{ request('protocol') == $protocol ? 'selected' : '' }}>
                                    {{ strtoupper($protocol) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Sorting -->
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Sort By</label>
                        <select name="sort" class="w-full border-gray-300 rounded-md text-sm shadow-sm">
                            <option value="premium" {{ request('sort') == 'premium' ? 'selected' : '' }}>Status (Premium First)</option>
                            <option value="speed" {{ request('sort') == 'speed' ? 'selected' : '' }}>Fastest Speed</option>
                            <option value="ping" {{ request('sort') == 'ping' ? 'selected' : '' }}>Lowest Latency</option>
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-medium py-2 rounded-md text-sm shadow transition">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Server Table -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Server / Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ping Latency</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Speed Cap</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Traffic (Bandwidth)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocols</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tier</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($servers as $server)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-bold text-gray-900">{{ $server->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $server->city }}, {{ $server->country }} ({{ $server->ip_address }})</div>
                                            <div class="text-xs text-gray-400 mt-0.5">Source: {{ $sourceDisplayNames[$server->source] ?? ucfirst($server->source) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span id="ping-{{ $server->id }}" class="inline-flex items-center text-xs font-semibold {{ $server->ping_ms < 50 ? 'text-green-600' : 'text-amber-600' }}">
                                        ⚡ {{ $server->ping_ms }} ms
                                    </span>
                                    <button onclick="pingServer({{ $server->id }})" class="ml-2 text-gray-400 hover:text-gray-600 text-xs">🔄</button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    🚀 {{ $server->speed_mbps }} Mbps
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    📊 {{ $server->bandwidth_used_gb }} GB used
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($server->configs as $config)
                                            @php
                                                $protocolColors = [
                                                    'ovpn' => 'bg-blue-100 text-blue-800 border-blue-300',
                                                    'v2ray' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                                    'wireguard' => 'bg-orange-100 text-orange-800 border-orange-300',
                                                    'shadowsocks' => 'bg-pink-100 text-pink-800 border-pink-300',
                                                ];
                                                $color = $protocolColors[$config->protocol] ?? 'bg-gray-100 text-gray-800 border-gray-300';
                                            @endphp
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full border {{ $color }}">
                                                {{ strtoupper($config->protocol) }}
                                            </span>
                                        @empty
                                            @if($server->ovpn_config)
                                                <span class="px-2 py-0.5 text-xs font-semibold rounded-full border bg-blue-100 text-blue-800 border-blue-300">
                                                    OVPN
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">N/A</span>
                                            @endif
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($server->is_premium)
                                        <span class="px-2 py-1 text-xs font-semibold bg-purple-100 text-purple-800 rounded-full">VIP Premium</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded-full">Free Node</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if($server->is_premium && !auth()->user()->hasActiveSubscription())
                                        <a href="{{ route('subscription.checkout') }}" class="text-xs text-indigo-600 hover:text-indigo-900 font-bold">Unlock Node</a>
                                    @else
                                        <div class="relative inline-block text-left" x-data="{ open: false }">
                                            <button @click="open = !open" class="inline-flex items-center px-3 py-1 bg-green-600 border border-transparent rounded-md text-xs font-bold text-white hover:bg-green-700">
                                                Download ▾
                                            </button>
                                            <div x-show="open" @click.away="open = false" x-cloak class="origin-top-right absolute right-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                                <div class="py-1">
                                                    @forelse($server->configs as $config)
                                                        <a href="{{ route('vpn.download', ['server' => $server, 'protocol' => $config->protocol]) }}" 
                                                           class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                                            Download {{ strtoupper($config->protocol) }}
                                                            @if($config->remote_port)
                                                                <span class="text-gray-400">(Port {{ $config->remote_port }})</span>
                                                            @endif
                                                        </a>
                                                    @empty
                                                        @if($server->ovpn_config)
                                                            <a href="{{ route('vpn.download', ['server' => $server, 'protocol' => 'ovpn']) }}" 
                                                               class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                                                Download OVPN
                                                            </a>
                                                        @else
                                                            @foreach(['ovpn', 'v2ray', 'wireguard', 'shadowsocks'] as $proto)
                                                                <a href="{{ route('vpn.download', ['server' => $server, 'protocol' => $proto]) }}" 
                                                                   class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100 hover:text-gray-900">
                                                                    Download {{ strtoupper($proto) }}
                                                                </a>
                                                            @endforeach
                                                        @endif
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500 text-sm">
                                    No VPN servers found. Run <code class="bg-gray-100 px-2 py-1 rounded">php artisan vpn:fetch-servers</code> to update the server list.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function pingServer(serverId) {
            const el = document.getElementById(`ping-${serverId}`);
            el.innerText = 'Testing...';
            
            fetch(`/vpn/${serverId}/ping`)
                .then(res => res.json())
                .then(data => {
                    el.innerText = `⚡ ${data.ping_ms} ms`;
                })
                .catch(() => {
                    el.innerText = 'Timeout';
                });
        }

        // Real-time refresh: fetch live servers & configs from sources.json URLs
        let isRefreshing = false;

        async function refreshServers() {
            if (isRefreshing) return;
            isRefreshing = true;

            const btn = document.getElementById('refresh-btn');
            const spinner = document.getElementById('refresh-spinner');
            const label = document.getElementById('refresh-label');
            const liveLabel = document.getElementById('live-label');
            const liveIndicatorDot = document.querySelector('#live-indicator span');
            const lastSyncedLabel = document.getElementById('last-synced-label');

            // Show loading state
            spinner.classList.remove('hidden');
            label.textContent = 'Syncing...';
            liveLabel.textContent = 'Syncing';
            liveIndicatorDot.classList.remove('bg-green-500');
            liveIndicatorDot.classList.add('bg-amber-500');

            try {
                const res = await fetch('/dashboard/refresh');
                const data = await res.json();

                if (data.success) {
                    // Update last-synced label
                    lastSyncedLabel.textContent = `Last synced: just now (${data.total_servers_synced} servers)`;
                    liveLabel.textContent = 'Live';
                    liveIndicatorDot.classList.remove('bg-amber-500');
                    liveIndicatorDot.classList.add('bg-green-500');

                    // Reload the page to reflect the freshly fetched servers & configs
                    window.location.reload();
                } else {
                    throw new Error('Refresh failed');
                }
            } catch (err) {
                liveLabel.textContent = 'Sync Error';
                liveIndicatorDot.classList.remove('bg-amber-500');
                liveIndicatorDot.classList.add('bg-red-500');
                label.textContent = 'Refresh Now';
            } finally {
                spinner.classList.add('hidden');
                isRefreshing = false;
            }
        }

        // Auto-refresh every 10 seconds
        setInterval(refreshServers, 10000);
    </script>
</x-app-layout>