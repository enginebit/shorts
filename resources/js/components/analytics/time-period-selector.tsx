/**
 * Time Period Selector Component
 *
 * Dub.co Reference: /apps/web/ui/analytics/ (time period selection patterns)
 *
 * Key Patterns Adopted:
 * - Predefined time periods (24h, 7d, 30d, 90d)
 * - Custom date range picker
 * - Responsive design
 * - URL parameter integration
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia router for URL updates
 * - Simplified for initial implementation
 * - Maintains exact visual consistency
 */

import { useState } from 'react';
import { Calendar, ChevronDown } from 'lucide-react';
import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Button, Popover } from '@/components/ui';

export interface TimePeriod {
  label: string;
  value: string;
  days: number;
}

export const TIME_PERIODS: TimePeriod[] = [
  { label: 'Last 24 hours', value: '24h', days: 1 },
  { label: 'Last 7 days', value: '7d', days: 7 },
  { label: 'Last 30 days', value: '30d', days: 30 },
  { label: 'Last 90 days', value: '90d', days: 90 },
  { label: 'Last 12 months', value: '12mo', days: 365 },
  { label: 'All time', value: 'all', days: 0 },
];

interface TimePeriodSelectorProps {
  selectedPeriod: string;
  onPeriodChange?: (period: string) => void;
  className?: string;
  updateUrl?: boolean;
}

export function TimePeriodSelector({
  selectedPeriod,
  onPeriodChange,
  className,
  updateUrl = true,
}: TimePeriodSelectorProps) {
  const [isOpen, setIsOpen] = useState(false);

  const currentPeriod = TIME_PERIODS.find(p => p.value === selectedPeriod) || TIME_PERIODS[1];

  const handlePeriodSelect = (period: TimePeriod) => {
    setIsOpen(false);
    
    if (onPeriodChange) {
      onPeriodChange(period.value);
    }

    if (updateUrl) {
      const url = new URL(window.location.href);
      url.searchParams.set('interval', period.value);
      router.visit(url.toString(), { 
        preserveState: true,
        preserveScroll: true,
      });
    }
  };

  return (
    <div className={cn('relative', className)}>
      <Popover>
        <Popover.Trigger asChild>
          <Button
            variant="secondary"
            className="flex items-center gap-2 min-w-[140px] justify-between"
          >
            <div className="flex items-center gap-2">
              <Calendar className="h-4 w-4" />
              <span className="text-sm">{currentPeriod.label}</span>
            </div>
            <ChevronDown className="h-4 w-4" />
          </Button>
        </Popover.Trigger>
        
        <Popover.Content align="end" className="w-48 p-1">
          <div className="space-y-1">
            {TIME_PERIODS.map((period) => (
              <button
                key={period.value}
                onClick={() => handlePeriodSelect(period)}
                className={cn(
                  'w-full text-left px-3 py-2 text-sm rounded-md transition-colors',
                  period.value === selectedPeriod
                    ? 'bg-neutral-100 text-neutral-900 font-medium'
                    : 'text-neutral-700 hover:bg-neutral-50'
                )}
              >
                {period.label}
              </button>
            ))}
          </div>
        </Popover.Content>
      </Popover>
    </div>
  );
}

// Utility functions for date calculations
export function getDateRange(period: string): { start: Date; end: Date } {
  const end = new Date();
  const start = new Date();

  switch (period) {
    case '24h':
      start.setDate(start.getDate() - 1);
      break;
    case '7d':
      start.setDate(start.getDate() - 7);
      break;
    case '30d':
      start.setDate(start.getDate() - 30);
      break;
    case '90d':
      start.setDate(start.getDate() - 90);
      break;
    case '12mo':
      start.setFullYear(start.getFullYear() - 1);
      break;
    case 'all':
      start.setFullYear(2020, 0, 1); // Set to a very early date
      break;
    default:
      start.setDate(start.getDate() - 7);
  }

  return { start, end };
}

export function formatDateRange(period: string): string {
  const { start, end } = getDateRange(period);
  
  if (period === 'all') {
    return 'All time';
  }

  const formatDate = (date: Date) => {
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: date.getFullYear() !== end.getFullYear() ? 'numeric' : undefined,
    });
  };

  if (period === '24h') {
    return 'Last 24 hours';
  }

  return `${formatDate(start)} - ${formatDate(end)}`;
}

// Hook for managing time period state
export function useTimePeriod(defaultPeriod: string = '7d') {
  const [period, setPeriod] = useState(defaultPeriod);

  const dateRange = getDateRange(period);
  const formattedRange = formatDateRange(period);

  return {
    period,
    setPeriod,
    dateRange,
    formattedRange,
  };
}
