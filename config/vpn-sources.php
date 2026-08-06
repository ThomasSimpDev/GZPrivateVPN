<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VPN Source Providers
    |--------------------------------------------------------------------------
    |
    | Register the VPN source provider classes that the application will
    | use to fetch VPN server data and configurations. Each source must
    | implement the VpnSourceInterface.
    |
    | Add new sources here and they will be automatically picked up by
    | the VpnProviderService during sync.
    |
    */

    'sources' => [
        \App\Services\VpnSources\VpnGateSource::class,
        \App\Services\VpnSources\ProxyFreeOnlySource::class,
        \App\Services\VpnSources\OpenProxyListSource::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    |
    */
    'batch_limit' => env('VPN_SYNC_BATCH_LIMIT', 50),

    'timeout' => env('VPN_SYNC_TIMEOUT', 10),
];

