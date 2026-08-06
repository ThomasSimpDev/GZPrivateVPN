<?php

namespace App\Providers;

use App\Services\VpnSources\VpnSourceManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VpnSourceManager::class, function ($app) {
            $manager = new VpnSourceManager();

            // Register all sources from config
            $sourceClasses = config('vpn-sources.sources', []);
            foreach ($sourceClasses as $sourceClass) {
                $source = $app->make($sourceClass);
                $manager->register($source);
            }

            // Assign URLs from sources.json to the matching source implementations
            $urls = $this->loadSourceUrls();
            $this->assignUrls($manager, $urls);

            return $manager;
        });
    }

    /**
     * Load the custom source URLs from the sources.json file.
     *
     * @return array<int, string>
     */
    private function loadSourceUrls(): array
    {
        $path = base_path('sources.json');
        if (!file_exists($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        $data = json_decode($raw, true);

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

    /**
     * Assign each source URL to the appropriate source implementation.
     */
    private function assignUrls(VpnSourceManager $manager, array $urls): void
    {
        foreach ($urls as $url) {
            foreach ($manager->all() as $source) {
                if ($source->handlesUrl($url)) {
                    $source->setUrl($url);
                    break;
                }
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
