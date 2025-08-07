<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WarmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm {--links : Warm link cache} {--tokens : Warm token cache} {--all : Warm all caches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up cache with frequently accessed data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔥 Warming Cache');
        $this->info('===============');
        $this->newLine();

        $warmLinks = $this->option('links') || $this->option('all');
        $warmTokens = $this->option('tokens') || $this->option('all');

        if (! $warmLinks && ! $warmTokens) {
            $this->warn('No cache type specified. Use --links, --tokens, or --all');

            return 1;
        }

        if ($warmLinks) {
            $this->warmLinkCache();
        }

        if ($warmTokens) {
            $this->warmTokenCache();
        }

        $this->info('✅ Cache warming completed successfully');

        return 0;
    }

    private function warmLinkCache(): void
    {
        $this->info('🔗 Warming Link Cache...');

        try {
            $linkCache = new \App\Services\Cache\LinkCacheService;

            // Get recent links (last 1000) to warm the cache
            $recentLinks = \App\Models\Link::with(['webhooks'])
                ->orderBy('updated_at', 'desc')
                ->limit(1000)
                ->get();

            if ($recentLinks->isNotEmpty()) {
                $linkCache->msetLinks($recentLinks);
                $this->info("✅ Cached {$recentLinks->count()} recent links");
            } else {
                $this->info('ℹ️  No links found to cache');
            }

            // Warm popular links (most clicked)
            $popularLinks = \App\Models\Link::with(['webhooks'])
                ->orderBy('clicks', 'desc')
                ->limit(500)
                ->get();

            if ($popularLinks->isNotEmpty()) {
                $linkCache->msetLinks($popularLinks);
                $this->info("✅ Cached {$popularLinks->count()} popular links");
            }

        } catch (\Exception $e) {
            $this->error('Failed to warm link cache: '.$e->getMessage());
        }
    }

    private function warmTokenCache(): void
    {
        $this->info('🔑 Warming Token Cache...');

        try {
            $tokenCache = new \App\Services\Cache\TokenCacheService;

            // JWKS cache will be warmed automatically when first JWT is validated
            $this->info('✅ Token cache service initialized');

            // Warm user workspace cache for active users
            $activeUsers = \App\Models\User::whereNotNull('supabase_id')
                ->where('updated_at', '>=', now()->subDays(7))
                ->limit(100)
                ->get();

            foreach ($activeUsers as $user) {
                $workspaces = $user->workspaces()->with(['users'])->get()->toArray();
                $tokenCache->setUserWorkspaces($user->id, $workspaces);
            }

            $this->info("✅ Cached workspace data for {$activeUsers->count()} active users");

        } catch (\Exception $e) {
            $this->error('Failed to warm token cache: '.$e->getMessage());
        }
    }
}
