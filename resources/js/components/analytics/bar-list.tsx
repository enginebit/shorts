/**
 * Bar List Component
 *
 * Dub.co Reference: /apps/web/ui/analytics/bar-list.tsx
 *
 * Key Patterns Adopted:
 * - Horizontal bar chart visualization
 * - Percentage-based bar widths
 * - Icon support for items
 * - Responsive design
 * - Loading and empty states
 *
 * Adaptations for Laravel + Inertia.js:
 * - Simplified for initial implementation
 * - Maintains exact visual consistency
 * - Supports various data types (countries, referrers, devices)
 */

import { ReactNode } from 'react';
import { Globe, ExternalLink, Monitor, Smartphone, Tablet } from 'lucide-react';
import { cn } from '@/lib/utils';

interface BarListItem {
  id: string;
  label: string;
  value: number;
  percentage: number;
  icon?: ReactNode;
  href?: string;
}

interface BarListProps {
  data: BarListItem[];
  loading?: boolean;
  emptyMessage?: string;
  className?: string;
  maxItems?: number;
  showPercentage?: boolean;
  showValue?: boolean;
}

export function BarList({
  data,
  loading = false,
  emptyMessage = 'No data available',
  className,
  maxItems = 10,
  showPercentage = true,
  showValue = true,
}: BarListProps) {
  const displayData = data.slice(0, maxItems);
  const maxValue = Math.max(...data.map(item => item.value));

  if (loading) {
    return (
      <div className={cn('space-y-3 p-4', className)}>
        {Array.from({ length: 5 }).map((_, i) => (
          <div key={i} className="flex items-center gap-3">
            <div className="h-4 w-4 bg-neutral-200 rounded animate-pulse" />
            <div className="flex-1">
              <div className="flex items-center justify-between mb-1">
                <div className="h-4 w-24 bg-neutral-200 rounded animate-pulse" />
                <div className="h-4 w-12 bg-neutral-200 rounded animate-pulse" />
              </div>
              <div className="h-2 bg-neutral-100 rounded-full">
                <div 
                  className="h-2 bg-neutral-200 rounded-full animate-pulse"
                  style={{ width: `${Math.random() * 80 + 20}%` }}
                />
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (displayData.length === 0) {
    return (
      <div className={cn('flex items-center justify-center p-8 text-center', className)}>
        <div>
          <Globe className="mx-auto h-8 w-8 text-neutral-400 mb-2" />
          <p className="text-sm text-neutral-500">{emptyMessage}</p>
        </div>
      </div>
    );
  }

  return (
    <div className={cn('space-y-3 p-4', className)}>
      {displayData.map((item, index) => {
        const barWidth = maxValue > 0 ? (item.value / maxValue) * 100 : 0;
        
        return (
          <div key={item.id} className="flex items-center gap-3">
            {/* Icon */}
            <div className="flex h-4 w-4 items-center justify-center flex-shrink-0">
              {item.icon || (
                <div className="h-2 w-2 rounded-full bg-neutral-400" />
              )}
            </div>

            {/* Content */}
            <div className="flex-1 min-w-0">
              <div className="flex items-center justify-between mb-1">
                <div className="flex items-center gap-2 min-w-0">
                  {item.href ? (
                    <a
                      href={item.href}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-sm font-medium text-neutral-900 hover:text-neutral-700 truncate flex items-center gap-1"
                    >
                      {item.label}
                      <ExternalLink className="h-3 w-3 flex-shrink-0" />
                    </a>
                  ) : (
                    <span className="text-sm font-medium text-neutral-900 truncate">
                      {item.label}
                    </span>
                  )}
                </div>
                
                <div className="flex items-center gap-2 text-sm text-neutral-600 flex-shrink-0">
                  {showValue && (
                    <span className="font-medium">
                      {item.value.toLocaleString()}
                    </span>
                  )}
                  {showPercentage && (
                    <span className="text-neutral-500">
                      {item.percentage.toFixed(1)}%
                    </span>
                  )}
                </div>
              </div>

              {/* Progress bar */}
              <div className="h-2 bg-neutral-100 rounded-full overflow-hidden">
                <div
                  className="h-2 bg-blue-500 rounded-full transition-all duration-300"
                  style={{ width: `${barWidth}%` }}
                />
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}

// Utility functions for creating bar list data
export function createCountryBarList(countries: Array<{ country: string; clicks: number }>): BarListItem[] {
  const totalClicks = countries.reduce((sum, item) => sum + item.clicks, 0);
  
  return countries.map((item, index) => ({
    id: `country-${index}`,
    label: item.country,
    value: item.clicks,
    percentage: totalClicks > 0 ? (item.clicks / totalClicks) * 100 : 0,
    icon: <Globe className="h-4 w-4 text-neutral-600" />,
  }));
}

export function createReferrerBarList(referrers: Array<{ referrer: string; clicks: number }>): BarListItem[] {
  const totalClicks = referrers.reduce((sum, item) => sum + item.clicks, 0);
  
  return referrers.map((item, index) => ({
    id: `referrer-${index}`,
    label: item.referrer || 'Direct',
    value: item.clicks,
    percentage: totalClicks > 0 ? (item.clicks / totalClicks) * 100 : 0,
    icon: <ExternalLink className="h-4 w-4 text-neutral-600" />,
    href: item.referrer && item.referrer !== 'Direct' ? `https://${item.referrer}` : undefined,
  }));
}

export function createDeviceBarList(devices: Array<{ device: string; clicks: number }>): BarListItem[] {
  const totalClicks = devices.reduce((sum, item) => sum + item.clicks, 0);
  
  const getDeviceIcon = (device: string) => {
    const deviceLower = device.toLowerCase();
    if (deviceLower.includes('mobile') || deviceLower.includes('phone')) {
      return <Smartphone className="h-4 w-4 text-neutral-600" />;
    }
    if (deviceLower.includes('tablet')) {
      return <Tablet className="h-4 w-4 text-neutral-600" />;
    }
    return <Monitor className="h-4 w-4 text-neutral-600" />;
  };
  
  return devices.map((item, index) => ({
    id: `device-${index}`,
    label: item.device,
    value: item.clicks,
    percentage: totalClicks > 0 ? (item.clicks / totalClicks) * 100 : 0,
    icon: getDeviceIcon(item.device),
  }));
}
