/**
 * Analytics Card Component
 *
 * Dub.co Reference: /apps/web/ui/analytics/analytics-card.tsx
 *
 * Key Patterns Adopted:
 * - Tabbed analytics card with modal expansion
 * - Icon-based tab navigation
 * - Responsive design with mobile optimization
 * - Modal view for detailed analytics
 * - Sub-tab support for detailed breakdowns
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses our Modal component instead of dub/ui Modal
 * - Simplified tab structure for initial implementation
 * - Maintains exact visual consistency
 * - Integrated with our analytics data structure
 */

import { ReactNode, useState } from 'react';
import { BarChart3, Users, Globe, MousePointer } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Modal, Dialog, Button } from '@/components/ui';

interface AnalyticsTab {
  id: string;
  label: string;
  icon: React.ComponentType<{ className?: string }>;
}

interface AnalyticsCardProps<T extends string> {
  tabs: AnalyticsTab[];
  selectedTabId: T;
  onSelectTab?: (tabId: T) => void;
  title?: string;
  children: (props: {
    limit?: number;
    setShowModal: (show: boolean) => void;
  }) => ReactNode;
  className?: string;
  expandLimit?: number;
  hasMore?: boolean;
}

export function AnalyticsCard<T extends string>({
  tabs,
  selectedTabId,
  onSelectTab,
  title,
  children,
  className,
  expandLimit = 5,
  hasMore = false,
}: AnalyticsCardProps<T>) {
  const [showModal, setShowModal] = useState(false);

  const selectedTab = tabs.find(({ id }) => id === selectedTabId) || tabs[0];
  const SelectedTabIcon = selectedTab.icon;

  return (
    <>
      {/* Modal for expanded view */}
      <Modal
        showModal={showModal}
        setShowModal={setShowModal}
        className="max-w-4xl"
      >
        <Dialog
          title={selectedTab?.label || title}
          description="Detailed analytics breakdown"
        >
          {children({ setShowModal })}
        </Dialog>
      </Modal>

      {/* Main card */}
      <div
        className={cn(
          'group relative z-0 h-[400px] overflow-hidden border border-neutral-200 bg-white sm:rounded-xl',
          className,
        )}
      >
        {/* Header with tabs */}
        <div className="flex items-center justify-between border-b border-neutral-200 px-4 py-3">
          {/* Tab navigation */}
          <div className="flex items-center gap-1">
            {tabs.map((tab) => {
              const TabIcon = tab.icon;
              const isSelected = tab.id === selectedTabId;
              
              return (
                <button
                  key={tab.id}
                  onClick={() => onSelectTab?.(tab.id as T)}
                  className={cn(
                    'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                    isSelected
                      ? 'bg-neutral-100 text-neutral-900'
                      : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900'
                  )}
                >
                  <TabIcon className="h-4 w-4" />
                  <span className="hidden sm:inline">{tab.label}</span>
                </button>
              );
            })}
          </div>

          {/* Expand button */}
          {hasMore && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setShowModal(true)}
              className="text-neutral-600 hover:text-neutral-900"
            >
              View All
            </Button>
          )}
        </div>

        {/* Content */}
        <div className="h-[calc(400px-57px)] overflow-hidden">
          {children({ limit: expandLimit, setShowModal })}
        </div>
      </div>
    </>
  );
}

// Pre-configured analytics tabs
export const ANALYTICS_TABS = {
  OVERVIEW: [
    { id: 'clicks', label: 'Clicks', icon: MousePointer },
    { id: 'visitors', label: 'Visitors', icon: Users },
    { id: 'countries', label: 'Countries', icon: Globe },
    { id: 'referrers', label: 'Referrers', icon: BarChart3 },
  ],
  LOCATIONS: [
    { id: 'countries', label: 'Countries', icon: Globe },
    { id: 'cities', label: 'Cities', icon: Globe },
  ],
  DEVICES: [
    { id: 'devices', label: 'Devices', icon: BarChart3 },
    { id: 'browsers', label: 'Browsers', icon: BarChart3 },
    { id: 'os', label: 'OS', icon: BarChart3 },
  ],
} as const;
