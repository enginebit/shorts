/**
 * NewBackground Component
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/shared/new-background.tsx
 *
 * Key Patterns Adopted:
 * - Animated gradient background
 * - Configurable animation and gradient options
 * - Fixed positioning for full-screen coverage
 * - Smooth gradient transitions
 *
 * Adaptations for Laravel + Inertia.js:
 * - Maintains exact visual consistency with dub-main
 * - Uses same animation patterns and timing
 */

import { cn } from '@/lib/utils';

interface NewBackgroundProps {
  showAnimation?: boolean;
  showGradient?: boolean;
  className?: string;
}

export function NewBackground({ 
  showAnimation = false, 
  showGradient = true,
  className 
}: NewBackgroundProps) {
  return (
    <div
      className={cn(
        "fixed inset-0 -z-10",
        showGradient && "bg-gradient-to-br from-neutral-50 via-white to-neutral-100",
        showAnimation && "animate-pulse",
        className
      )}
    >
      {showAnimation && (
        <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer" />
      )}
    </div>
  );
}
