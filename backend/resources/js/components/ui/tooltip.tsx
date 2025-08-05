/**
 * Tooltip Component (Simplified)
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/packages/ui/src/tooltip.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Simplified implementation without Radix UI for now
 * - Will be enhanced with full Radix UI integration later
 * - Maintains basic tooltip functionality for Button component
 */

import { cn } from '@/lib/utils';
import { ReactNode, useState } from 'react';

export interface TooltipProps {
  children: ReactNode;
  content: ReactNode | string;
  contentClassName?: string;
  disabled?: boolean;
  side?: 'top' | 'bottom' | 'left' | 'right';
}

export function Tooltip({
  children,
  content,
  contentClassName,
  disabled,
  side = 'top',
}: TooltipProps) {
  const [isVisible, setIsVisible] = useState(false);

  if (disabled) {
    return <>{children}</>;
  }

  return (
    <div
      className="relative inline-block"
      onMouseEnter={() => setIsVisible(true)}
      onMouseLeave={() => setIsVisible(false)}
    >
      {children}
      {isVisible && (
        <div
          className={cn(
            'absolute z-50 rounded-md bg-neutral-900 px-3 py-1.5 text-xs text-white shadow-md',
            {
              'bottom-full left-1/2 mb-2 -translate-x-1/2': side === 'top',
              'top-full left-1/2 mt-2 -translate-x-1/2': side === 'bottom',
              'right-full top-1/2 mr-2 -translate-y-1/2': side === 'left',
              'left-full top-1/2 ml-2 -translate-y-1/2': side === 'right',
            },
            contentClassName,
          )}
        >
          {content}
          <div
            className={cn('absolute h-2 w-2 rotate-45 bg-neutral-900', {
              'top-full left-1/2 -translate-x-1/2 -translate-y-1/2': side === 'top',
              'bottom-full left-1/2 -translate-x-1/2 translate-y-1/2': side === 'bottom',
              'top-1/2 left-full -translate-x-1/2 -translate-y-1/2': side === 'left',
              'top-1/2 right-full translate-x-1/2 -translate-y-1/2': side === 'right',
            })}
          />
        </div>
      )}
    </div>
  );
}
