<?php

namespace App\Services\VpnSources;

class VpnSourceManager
{
    /** @var VpnSourceInterface[] */
    private array $sources = [];

    /**
     * Register a new source.
     */
    public function register(VpnSourceInterface $source): void
    {
        $this->sources[$source->getIdentifier()] = $source;
    }

    /**
     * Get a source by its identifier.
     */
    public function get(string $identifier): ?VpnSourceInterface
    {
        return $this->sources[$identifier] ?? null;
    }

    /**
     * Get all registered sources.
     *
     * @return VpnSourceInterface[]
     */
    public function all(): array
    {
        return $this->sources;
    }

    /**
     * Fetch servers from all registered sources.
     *
     * @return array<int, array> Aggregated list of normalized server data
     */
    public function fetchAll(): array
    {
        $allServers = [];

        foreach ($this->sources as $source) {
            try {
                $servers = $source->fetch();
                $allServers = array_merge($allServers, $servers);
            } catch (\Exception $e) {
                // Log and skip this source if it fails
                logger()->warning("Failed to fetch from source {$source->getIdentifier()}: {$e->getMessage()}");
            }
        }

        return $allServers;
    }

    /**
     * Get all available source identifiers.
     *
     * @return string[]
     */
    public function getIdentifiers(): array
    {
        return array_keys($this->sources);
    }

    /**
     * Get all source display names keyed by identifier.
     *
     * @return array<string, string>
     */
    public function getDisplayNames(): array
    {
        $names = [];
        foreach ($this->sources as $source) {
            $names[$source->getIdentifier()] = $source->getName();
        }
        return $names;
    }

    /**
     * Get the configured endpoint URL for a source identifier.
     */
    public function getSourceUrl(string $identifier): ?string
    {
        $source = $this->get($identifier);
        return $source?->getUrl();
    }

    /**
     * Fetch and return normalized server data from a single source by URL.
     * Used for real-time refresh of a specific source endpoint.
     *
     * @return array
     */
    public function fetchSourceByUrl(string $url): array
    {
        foreach ($this->sources as $source) {
            if ($source->handlesUrl($url)) {
                $source->setUrl($url);
                return $source->fetch();
            }
        }

        return [];
    }
}

