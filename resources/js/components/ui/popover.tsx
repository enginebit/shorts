/**
 * Popover Component
 *
 * Dub.co Reference: /packages/ui/src/popover.tsx
 *
 * Key Patterns Adopted:
 * - Controlled open/close state
 * - Flexible positioning (align, side)
 * - Mobile-responsive behavior
 * - Backdrop click to close
 * - Escape key handling
 *
 * Adaptations for Laravel + Inertia.js:
 * - Simplified implementation without Radix UI for now
 * - Uses our useMediaQuery hook for responsive behavior
 * - Maintains exact dub-main API and styling
 * - Will be enhanced with Radix UI integration later
 */

import { cn } from '@/lib/utils';
import { useBreakpoint } from '@/hooks/use-media-query';
import { ReactNode, useEffect, useRef } from 'react';

export interface PopoverProps {
  children: ReactNode;
  content: ReactNode | string;
  align?: 'center' | 'start' | 'end';
  side?: 'bottom' | 'top' | 'left' | 'right';
  openPopover: boolean;
  setOpenPopover: (open: boolean) => void;
  mobileOnly?: boolean;
  popoverContentClassName?: string;
  onEscapeKeyDown?: (event: KeyboardEvent) => void;
}

export function Popover({
  children,
  content,
  align = 'center',
  side = 'bottom',
  openPopover,
  setOpenPopover,
  mobileOnly = false,
  popoverContentClassName,
  onEscapeKeyDown,
}: PopoverProps) {
  const { isMobile } = useBreakpoint();
  const popoverRef = useRef<HTMLDivElement>(null);

  // Handle escape key
  useEffect(() => {
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape' && openPopover) {
        if (onEscapeKeyDown) {
          onEscapeKeyDown(event);
        } else {
          setOpenPopover(false);
        }
      }
    };

    if (openPopover) {
      document.addEventListener('keydown', handleEscape);
    }

    return () => {
      document.removeEventListener('keydown', handleEscape);
    };
  }, [openPopover, onEscapeKeyDown, setOpenPopover]);

  // Handle click outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        popoverRef.current &&
        !popoverRef.current.contains(event.target as Node) &&
        openPopover
      ) {
        setOpenPopover(false);
      }
    };

    if (openPopover) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [openPopover, setOpenPopover]);

  const getPositionClasses = () => {
    const positions = {
      bottom: {
        center: 'top-full left-1/2 -translate-x-1/2 mt-2',
        start: 'top-full left-0 mt-2',
        end: 'top-full right-0 mt-2',
      },
      top: {
        center: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
        start: 'bottom-full left-0 mb-2',
        end: 'bottom-full right-0 mb-2',
      },
      left: {
        center: 'right-full top-1/2 -translate-y-1/2 mr-2',
        start: 'right-full top-0 mr-2',
        end: 'right-full bottom-0 mr-2',
      },
      right: {
        center: 'left-full top-1/2 -translate-y-1/2 ml-2',
        start: 'left-full top-0 ml-2',
        end: 'left-full bottom-0 ml-2',
      },
    };

    return positions[side][align];
  };

  // For mobile, show as a bottom sheet (simplified)
  if (mobileOnly || isMobile) {
    return (
      <div className="relative">
        {children}
        {openPopover && (
          <>
            {/* Backdrop */}
            <div
              className="fixed inset-0 z-50 bg-black/20 backdrop-blur-sm"
              onClick={() => setOpenPopover(false)}
            />
            {/* Mobile drawer */}
            <div
              ref={popoverRef}
              className={cn(
                'fixed bottom-0 left-0 right-0 z-50 rounded-t-xl border-t border-neutral-200 bg-white p-4 shadow-xl',
                popoverContentClassName,
              )}
            >
              {content}
            </div>
          </>
        )}
      </div>
    );
  }

  return (
    <div className="relative">
      {children}
      {openPopover && (
        <div
          ref={popoverRef}
          className={cn(
            'absolute z-50 min-w-32 rounded-lg border border-neutral-200 bg-white p-1 shadow-lg',
            getPositionClasses(),
            popoverContentClassName,
          )}
        >
          {content}
        </div>
      )}
    </div>
  );
}
