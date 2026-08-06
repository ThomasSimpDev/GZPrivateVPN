<?php

namespace App\Console\Commands;

use App\Services\VpnProviderService;
use Illuminate\Console\Command;

class FetchVpnServers extends Command
{
    protected $signature = 'vpn:fetch-servers';
    protected $description = 'Sync list of VPN servers from all provider sources';

    public function handle(VpnProviderService $service): int
    {
        $this->info('Fetching VPN servers from all sources...');
        $count = $service->syncExternalServers();
        $this->info("Successfully updated {$count} VPN servers.");
        return Command::SUCCESS;
    }
}

