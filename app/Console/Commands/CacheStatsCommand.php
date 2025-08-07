<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CacheStatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:stats {--detailed : Show detailed statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display cache statistics for all cache services';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📊 Cache Statistics');
        $this->info('==================');
        $this->newLine();

        // Link Cache Stats
        $linkCache = new \App\Services\Cache\LinkCacheService;
        $linkStats = $linkCache->getStats();

        $this->info('🔗 Link Cache:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cached Links', $linkStats['total_cached_links']],
                ['Cache Prefix', $linkStats['cache_prefix']],
                ['Connection', $linkStats['connection']],
                ['Default TTL', $linkStats['default_ttl'].' seconds'],
            ]
        );
        $this->newLine();

        // Token Cache Stats
        $tokenCache = new \App\Services\Cache\TokenCacheService;
        $tokenStats = $tokenCache->getStats();

        $this->info('🔑 Token Cache:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cached Tokens', $tokenStats['total_cached_tokens']],
                ['Cache Prefix', $tokenStats['cache_prefix']],
                ['Connection', $tokenStats['connection']],
                ['Default TTL', $tokenStats['default_ttl'].' seconds'],
                ['JWKS TTL', $tokenStats['jwks_ttl'].' seconds'],
            ]
        );
        $this->newLine();

        // Analytics Cache Stats
        $analyticsCache = new \App\Services\Cache\AnalyticsCacheService;
        $analyticsStats = $analyticsCache->getStats();

        $this->info('📈 Analytics Cache:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Analytics Keys', $analyticsStats['total_analytics_keys']],
                ['Cache Prefix', $analyticsStats['cache_prefix']],
                ['Connection', $analyticsStats['connection']],
                ['Default TTL', $analyticsStats['default_ttl'].' seconds'],
                ['Domain TTL', $analyticsStats['domain_ttl'].' seconds'],
            ]
        );
        $this->newLine();

        // Webhook Cache Stats
        $webhookCache = new \App\Services\Cache\WebhookCacheService;
        $webhookStats = $webhookCache->getStats();

        $this->info('🪝 Webhook Cache:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cached Webhooks', $webhookStats['total_cached_webhooks']],
                ['Cache Prefix', $webhookStats['cache_prefix']],
                ['Connection', $webhookStats['connection']],
                ['Permanent Cache', $webhookStats['permanent_cache'] ? 'Yes' : 'No'],
            ]
        );
        $this->newLine();

        if ($this->option('detailed')) {
            $this->showDetailedStats();
        }

        $this->info('✅ Cache statistics displayed successfully');

        return 0;
    }

    private function showDetailedStats(): void
    {
        $this->info('📋 Detailed Statistics');
        $this->info('=====================');
        $this->newLine();

        // Redis connection info
        try {
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $info = $redis->info();

            $this->info('🔧 Redis Server Info:');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Redis Version', $info['redis_version'] ?? 'Unknown'],
                    ['Used Memory', $info['used_memory_human'] ?? 'Unknown'],
                    ['Connected Clients', $info['connected_clients'] ?? 'Unknown'],
                    ['Total Commands', $info['total_commands_processed'] ?? 'Unknown'],
                    ['Keyspace Hits', $info['keyspace_hits'] ?? 'Unknown'],
                    ['Keyspace Misses', $info['keyspace_misses'] ?? 'Unknown'],
                ]
            );

            // Hit ratio
            $hits = (int) ($info['keyspace_hits'] ?? 0);
            $misses = (int) ($info['keyspace_misses'] ?? 0);
            $total = $hits + $misses;
            $hitRatio = $total > 0 ? round(($hits / $total) * 100, 2) : 0;

            $this->newLine();
            $this->info("🎯 Cache Hit Ratio: {$hitRatio}%");

        } catch (\Exception $e) {
            $this->error('Failed to get Redis info: '.$e->getMessage());
        }
    }
}
