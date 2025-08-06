/**
 * Workspace Analytics Page
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
 * - Placeholder implementation for Phase 3A
 * - Will be fully implemented in Phase 3B
 * - Maintains exact visual consistency with dub-main
 */

import { Head } from '@inertiajs/react';
import { BarChart3, TrendingUp, Globe, Users, Calendar, ArrowUpRight } from 'lucide-react';
import { AppLayout } from '@/layouts/app-layout';
import { 
  PageWidthWrapper, 
  Button, 
  PageHeader 
} from '@/components/ui';
import { useWorkspace } from '@/contexts/workspace-context';

interface AnalyticsPageProps {
  workspace: {
    id: string;
    name: string;
    slug: string;
  };
}

function ComingSoonCard({ 
  title, 
  description, 
  icon: Icon 
}: {
  title: string;
  description: string;
  icon: React.ComponentType<{ className?: string }>;
}) {
  return (
    <div className="rounded-lg border border-neutral-200 bg-white p-6">
      <div className="flex items-center justify-between mb-4">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-neutral-100">
            <Icon className="h-5 w-5 text-neutral-600" />
          </div>
          <div>
            <h3 className="font-semibold text-neutral-900">{title}</h3>
            <p className="text-sm text-neutral-600">{description}</p>
          </div>
        </div>
        <div className="text-right">
          <span className="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
            Coming Soon
          </span>
        </div>
      </div>
      
      <div className="h-32 rounded-lg bg-neutral-50 flex items-center justify-center">
        <div className="text-center">
          <Icon className="mx-auto h-8 w-8 text-neutral-400 mb-2" />
          <p className="text-sm text-neutral-500">Analytics visualization</p>
        </div>
      </div>
    </div>
  );
}

function PlaceholderMetric({ 
  label, 
  value, 
  change, 
  icon: Icon 
}: {
  label: string;
  value: string;
  change: string;
  icon: React.ComponentType<{ className?: string }>;
}) {
  return (
    <div className="rounded-lg border border-neutral-200 bg-white p-6">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-neutral-600">{label}</p>
          <p className="text-2xl font-bold text-neutral-900">{value}</p>
          <div className="flex items-center gap-1 mt-1">
            <ArrowUpRight className="h-3 w-3 text-green-600" />
            <span className="text-sm text-green-600">{change}</span>
          </div>
        </div>
        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-neutral-50">
          <Icon className="h-6 w-6 text-neutral-600" />
        </div>
      </div>
    </div>
  );
}

export default function AnalyticsPage({ workspace }: AnalyticsPageProps) {
  return (
    <AppLayout>
      <Head title={`Analytics - ${workspace.name}`} />

      <PageHeader
        title="Analytics"
        description="Track your link performance and audience insights"
      />

      <PageWidthWrapper className="py-8">
        <div className="space-y-8">
          {/* Phase 3B Notice */}
          <div className="rounded-lg border border-blue-200 bg-blue-50 p-6">
            <div className="flex items-start gap-4">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100">
                <BarChart3 className="h-5 w-5 text-blue-600" />
              </div>
              <div className="flex-1">
                <h3 className="font-semibold text-blue-900">Analytics Dashboard Coming Soon</h3>
                <p className="text-sm text-blue-700 mt-1">
                  Comprehensive analytics with real-time click tracking, geographic insights, and performance metrics 
                  will be implemented in Phase 3B: Core Features.
                </p>
                <div className="mt-4">
                  <Button variant="secondary" size="sm">
                    <Calendar className="h-4 w-4 mr-2" />
                    View Roadmap
                  </Button>
                </div>
              </div>
            </div>
          </div>

          {/* Placeholder Metrics */}
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <PlaceholderMetric
              label="Total Clicks"
              value="12,345"
              change="+12.5% from last month"
              icon={BarChart3}
            />
            <PlaceholderMetric
              label="Unique Visitors"
              value="8,901"
              change="+8.2% from last month"
              icon={Users}
            />
            <PlaceholderMetric
              label="Top Countries"
              value="23"
              change="+3 new countries"
              icon={Globe}
            />
            <PlaceholderMetric
              label="Conversion Rate"
              value="3.4%"
              change="+0.8% from last month"
              icon={TrendingUp}
            />
          </div>

          {/* Coming Soon Cards */}
          <div className="grid gap-6 lg:grid-cols-2">
            <ComingSoonCard
              title="Click Analytics"
              description="Real-time click tracking and performance metrics"
              icon={BarChart3}
            />
            <ComingSoonCard
              title="Geographic Insights"
              description="See where your audience is located worldwide"
              icon={Globe}
            />
            <ComingSoonCard
              title="Referrer Analysis"
              description="Track which sources drive the most traffic"
              icon={TrendingUp}
            />
            <ComingSoonCard
              title="Device & Browser Stats"
              description="Understand your audience's technology preferences"
              icon={Users}
            />
          </div>

          {/* Feature Preview */}
          <div className="rounded-lg border border-neutral-200 bg-white p-8">
            <div className="text-center">
              <div className="mx-auto h-16 w-16 rounded-full bg-neutral-100 flex items-center justify-center mb-4">
                <BarChart3 className="h-8 w-8 text-neutral-600" />
              </div>
              <h3 className="text-lg font-semibold text-neutral-900 mb-2">
                Advanced Analytics Coming Soon
              </h3>
              <p className="text-neutral-600 max-w-2xl mx-auto mb-6">
                Get detailed insights into your link performance with real-time analytics, 
                geographic data, referrer tracking, and conversion metrics. Our analytics 
                dashboard will help you understand your audience and optimize your campaigns.
              </p>
              <div className="flex flex-wrap justify-center gap-4 text-sm text-neutral-500">
                <span className="flex items-center gap-1">
                  <BarChart3 className="h-4 w-4" />
                  Real-time tracking
                </span>
                <span className="flex items-center gap-1">
                  <Globe className="h-4 w-4" />
                  Geographic insights
                </span>
                <span className="flex items-center gap-1">
                  <Users className="h-4 w-4" />
                  Audience analytics
                </span>
                <span className="flex items-center gap-1">
                  <TrendingUp className="h-4 w-4" />
                  Performance metrics
                </span>
              </div>
            </div>
          </div>
        </div>
      </PageWidthWrapper>
    </AppLayout>
  );
}
