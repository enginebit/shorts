/**
 * Workspace Analytics Page - Full Implementation
 *
 * Dub.co Reference: /apps/web/app/app.dub.co/(dashboard)/[slug]/analytics/client.tsx
 *
 * Key Patterns Adopted:
 * - Analytics dashboard with charts and metrics
 * - Time period filtering and data visualization
 * - Click tracking and performance metrics
 * - Geographic and referrer analytics
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia page props for analytics data
 * - Full implementation for Phase 3B
 * - Integrated with AnalyticsController API
 * - Maintains exact visual consistency with dub-main
 */

import { Head, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { BarChart3, TrendingUp, Globe, Users, MousePointer, ExternalLink, RefreshCw } from 'lucide-react';
import { AppLayout } from '@/layouts/app-layout';
import { 
  PageWidthWrapper, 
  Button, 
  PageHeader 
} from '@/components/ui';
import { useWorkspace } from '@/contexts/workspace-context';
import { AnalyticsCard, ANALYTICS_TABS } from '@/components/analytics/analytics-card';
import { 
  MetricsCard, 
  ClicksMetricsCard, 
  VisitorsMetricsCard, 
  ConversionRateCard 
} from '@/components/analytics/metrics-card';
import { 
  BarList, 
  createCountryBarList, 
  createReferrerBarList, 
  createDeviceBarList 
} from '@/components/analytics/bar-list';
import { TimePeriodSelector, useTimePeriod } from '@/components/analytics/time-period-selector';
import { toast } from 'sonner';

interface AnalyticsData {
  clicks: {
    total: number;
    change: number;
    timeseries: Array<{ date: string; clicks: number }>;
  };
  visitors: {
    total: number;
    change: number;
  };
  conversionRate: {
    rate: number;
    change: number;
  };
  topCountries: Array<{ country: string; clicks: number }>;
  topReferrers: Array<{ referrer: string; clicks: number }>;
  topDevices: Array<{ device: string; clicks: number }>;
  topLinks: Array<{ 
    id: string; 
    url: string; 
    shortLink: string; 
    clicks: number; 
    title?: string; 
  }>;
}

interface AnalyticsPageProps {
  workspace: {
    id: string;
    name: string;
    slug: string;
  };
  initialData?: AnalyticsData;
  interval?: string;
}

export default function AnalyticsPage({ 
  workspace, 
  initialData,
  interval = '7d' 
}: AnalyticsPageProps) {
  const [analyticsData, setAnalyticsData] = useState<AnalyticsData | null>(initialData || null);
  const [loading, setLoading] = useState(!initialData);
  const [error, setError] = useState<string | null>(null);
  const { period, setPeriod } = useTimePeriod(interval);
  const [selectedTab, setSelectedTab] = useState<string>('clicks');

  // Fetch analytics data
  const fetchAnalytics = async (selectedPeriod: string = period) => {
    try {
      setLoading(true);
      setError(null);

      const response = await fetch(
        route('api.analytics.overview', {
          workspaceId: workspace.id,
          interval: selectedPeriod,
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        })
      );

      if (!response.ok) {
        throw new Error('Failed to fetch analytics data');
      }

      const data = await response.json();
      setAnalyticsData(data);
    } catch (err) {
      console.error('Analytics fetch error:', err);
      setError('Failed to load analytics data');
      toast.error('Failed to load analytics data');
    } finally {
      setLoading(false);
    }
  };

  // Fetch data when period changes
  useEffect(() => {
    fetchAnalytics(period);
  }, [period, workspace.id]);

  const handlePeriodChange = (newPeriod: string) => {
    setPeriod(newPeriod);
  };

  const handleRefresh = () => {
    fetchAnalytics();
  };

  return (
    <AppLayout>
      <Head title={`Analytics - ${workspace.name}`} />

      <PageHeader
        title="Analytics"
        description="Track your link performance and audience insights"
        action={
          <div className="flex items-center gap-3">
            <Button
              variant="secondary"
              size="sm"
              onClick={handleRefresh}
              disabled={loading}
            >
              <RefreshCw className={`h-4 w-4 mr-2 ${loading ? 'animate-spin' : ''}`} />
              Refresh
            </Button>
            <TimePeriodSelector
              selectedPeriod={period}
              onPeriodChange={handlePeriodChange}
            />
          </div>
        }
      />

      <PageWidthWrapper className="py-8">
        <div className="space-y-8">
          {/* Key Metrics */}
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <ClicksMetricsCard
              clicks={analyticsData?.clicks.total || 0}
              change={analyticsData?.clicks.change}
              loading={loading}
              error={error}
            />
            <VisitorsMetricsCard
              visitors={analyticsData?.visitors.total || 0}
              change={analyticsData?.visitors.change}
              loading={loading}
              error={error}
            />
            <ConversionRateCard
              rate={analyticsData?.conversionRate.rate || 0}
              change={analyticsData?.conversionRate.change}
              loading={loading}
              error={error}
            />
            <MetricsCard
              title="Top Performing Link"
              value={analyticsData?.topLinks?.[0]?.clicks || 0}
              icon={MousePointer}
              loading={loading}
              error={error}
            >
              {analyticsData?.topLinks?.[0] && (
                <p className="text-xs text-neutral-500 truncate mt-1">
                  {analyticsData.topLinks[0].title || analyticsData.topLinks[0].shortLink}
                </p>
              )}
            </MetricsCard>
          </div>

          {/* Analytics Cards Grid */}
          <div className="grid gap-6 lg:grid-cols-2">
            {/* Countries */}
            <AnalyticsCard
              tabs={ANALYTICS_TABS.LOCATIONS}
              selectedTabId="countries"
              title="Top Countries"
              expandLimit={5}
              hasMore={(analyticsData?.topCountries?.length || 0) > 5}
            >
              {({ limit }) => (
                <BarList
                  data={createCountryBarList(analyticsData?.topCountries || [])}
                  loading={loading}
                  emptyMessage="No geographic data available"
                  maxItems={limit}
                />
              )}
            </AnalyticsCard>

            {/* Referrers */}
            <AnalyticsCard
              tabs={[{ id: 'referrers', label: 'Referrers', icon: ExternalLink }]}
              selectedTabId="referrers"
              title="Top Referrers"
              expandLimit={5}
              hasMore={(analyticsData?.topReferrers?.length || 0) > 5}
            >
              {({ limit }) => (
                <BarList
                  data={createReferrerBarList(analyticsData?.topReferrers || [])}
                  loading={loading}
                  emptyMessage="No referrer data available"
                  maxItems={limit}
                />
              )}
            </AnalyticsCard>

            {/* Devices */}
            <AnalyticsCard
              tabs={ANALYTICS_TABS.DEVICES}
              selectedTabId="devices"
              title="Devices"
              expandLimit={5}
              hasMore={(analyticsData?.topDevices?.length || 0) > 5}
            >
              {({ limit }) => (
                <BarList
                  data={createDeviceBarList(analyticsData?.topDevices || [])}
                  loading={loading}
                  emptyMessage="No device data available"
                  maxItems={limit}
                />
              )}
            </AnalyticsCard>

            {/* Top Links */}
            <AnalyticsCard
              tabs={[{ id: 'links', label: 'Top Links', icon: MousePointer }]}
              selectedTabId="links"
              title="Top Performing Links"
              expandLimit={5}
              hasMore={(analyticsData?.topLinks?.length || 0) > 5}
            >
              {({ limit }) => (
                <div className="p-4 space-y-3">
                  {loading ? (
                    Array.from({ length: 3 }).map((_, i) => (
                      <div key={i} className="animate-pulse">
                        <div className="h-4 bg-neutral-200 rounded mb-2" />
                        <div className="h-3 bg-neutral-200 rounded w-3/4" />
                      </div>
                    ))
                  ) : analyticsData?.topLinks?.slice(0, limit).map((link) => (
                    <div key={link.id} className="flex items-center justify-between">
                      <div className="min-w-0 flex-1">
                        <p className="text-sm font-medium text-neutral-900 truncate">
                          {link.title || link.shortLink}
                        </p>
                        <p className="text-xs text-neutral-500 truncate">
                          {link.url}
                        </p>
                      </div>
                      <div className="text-sm font-medium text-neutral-900 ml-4">
                        {link.clicks.toLocaleString()}
                      </div>
                    </div>
                  )) || (
                    <div className="text-center py-8">
                      <MousePointer className="mx-auto h-8 w-8 text-neutral-400 mb-2" />
                      <p className="text-sm text-neutral-500">No link data available</p>
                    </div>
                  )}
                </div>
              )}
            </AnalyticsCard>
          </div>
        </div>
      </PageWidthWrapper>
    </AppLayout>
  );
}
