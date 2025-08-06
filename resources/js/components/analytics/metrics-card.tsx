/**
 * Metrics Card Component
 *
 * Dub.co Reference: /apps/web/ui/analytics/ (various metric display patterns)
 *
 * Key Patterns Adopted:
 * - Clean metric display with icon and trend
 * - Percentage change indicators
 * - Color-coded trend indicators
 * - Responsive design
 * - Loading states
 *
 * Adaptations for Laravel + Inertia.js:
 * - Simplified for initial implementation
 * - Maintains exact visual consistency
 * - Supports loading and error states
 */

import { ReactNode } from 'react';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { cn } from '@/lib/utils';
import { LoadingSpinner } from '@/components/ui';

interface MetricsCardProps {
  title: string;
  value: string | number;
  change?: number;
  changeLabel?: string;
  icon?: React.ComponentType<{ className?: string }>;
  loading?: boolean;
  error?: string;
  className?: string;
  children?: ReactNode;
}

export function MetricsCard({
  title,
  value,
  change,
  changeLabel,
  icon: Icon,
  loading = false,
  error,
  className,
  children,
}: MetricsCardProps) {
  const formatValue = (val: string | number) => {
    if (typeof val === 'number') {
      if (val >= 1000000) {
        return `${(val / 1000000).toFixed(1)}M`;
      }
      if (val >= 1000) {
        return `${(val / 1000).toFixed(1)}K`;
      }
      return val.toLocaleString();
    }
    return val;
  };

  const getTrendIcon = () => {
    if (change === undefined || change === 0) return Minus;
    return change > 0 ? TrendingUp : TrendingDown;
  };

  const getTrendColor = () => {
    if (change === undefined || change === 0) return 'text-neutral-500';
    return change > 0 ? 'text-green-600' : 'text-red-600';
  };

  const TrendIcon = getTrendIcon();

  if (loading) {
    return (
      <div className={cn(
        'rounded-lg border border-neutral-200 bg-white p-6',
        className
      )}>
        <div className="flex items-center justify-between">
          <div className="space-y-2">
            <div className="h-4 w-20 bg-neutral-200 rounded animate-pulse" />
            <div className="h-8 w-16 bg-neutral-200 rounded animate-pulse" />
            <div className="h-3 w-24 bg-neutral-200 rounded animate-pulse" />
          </div>
          {Icon && (
            <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-neutral-50">
              <div className="h-6 w-6 bg-neutral-200 rounded animate-pulse" />
            </div>
          )}
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className={cn(
        'rounded-lg border border-red-200 bg-red-50 p-6',
        className
      )}>
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-red-800">{title}</p>
            <p className="text-sm text-red-600">{error}</p>
          </div>
          {Icon && (
            <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-red-100">
              <Icon className="h-6 w-6 text-red-600" />
            </div>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className={cn(
      'rounded-lg border border-neutral-200 bg-white p-6',
      className
    )}>
      <div className="flex items-center justify-between">
        <div className="space-y-1">
          <p className="text-sm font-medium text-neutral-600">{title}</p>
          <p className="text-2xl font-bold text-neutral-900">
            {formatValue(value)}
          </p>
          
          {change !== undefined && (
            <div className={cn('flex items-center gap-1', getTrendColor())}>
              <TrendIcon className="h-3 w-3" />
              <span className="text-sm font-medium">
                {Math.abs(change)}%
              </span>
              {changeLabel && (
                <span className="text-sm text-neutral-500">
                  {changeLabel}
                </span>
              )}
            </div>
          )}
          
          {children}
        </div>
        
        {Icon && (
          <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-neutral-50">
            <Icon className="h-6 w-6 text-neutral-600" />
          </div>
        )}
      </div>
    </div>
  );
}

// Specialized metric cards
export function ClicksMetricsCard({ 
  clicks, 
  change, 
  loading, 
  error 
}: { 
  clicks: number; 
  change?: number; 
  loading?: boolean; 
  error?: string; 
}) {
  return (
    <MetricsCard
      title="Total Clicks"
      value={clicks}
      change={change}
      changeLabel="vs last period"
      icon={({ className }) => (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.122 2.122" />
        </svg>
      )}
      loading={loading}
      error={error}
    />
  );
}

export function VisitorsMetricsCard({ 
  visitors, 
  change, 
  loading, 
  error 
}: { 
  visitors: number; 
  change?: number; 
  loading?: boolean; 
  error?: string; 
}) {
  return (
    <MetricsCard
      title="Unique Visitors"
      value={visitors}
      change={change}
      changeLabel="vs last period"
      icon={({ className }) => (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
        </svg>
      )}
      loading={loading}
      error={error}
    />
  );
}

export function ConversionRateCard({ 
  rate, 
  change, 
  loading, 
  error 
}: { 
  rate: number; 
  change?: number; 
  loading?: boolean; 
  error?: string; 
}) {
  return (
    <MetricsCard
      title="Conversion Rate"
      value={`${rate.toFixed(1)}%`}
      change={change}
      changeLabel="vs last period"
      icon={({ className }) => (
        <svg className={className} fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
      )}
      loading={loading}
      error={error}
    />
  );
}
