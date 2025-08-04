/**
 * LoadingSpinner Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/packages/ui/src/icons/loading-spinner.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced @dub/utils with local utils
 * - Maintained exact visual consistency with dub-main
 */

import { cn } from '@/lib/utils';

export function LoadingSpinner({ className }: { className?: string }) {
  return (
    <div className={cn('h-5 w-5', className)}>
      <div
        style={{
          position: 'relative',
          top: '50%',
          left: '50%',
        }}
        className={cn('loading-spinner', 'h-5 w-5', className)}
      >
        {[...Array(12)].map((_, i) => (
          <div
            key={i}
            style={{
              animationDelay: `${-1.2 + 0.1 * i}s`,
              background: 'gray',
              position: 'absolute',
              borderRadius: '1rem',
              width: '30%',
              height: '8%',
              left: '-10%',
              top: '-4%',
              transform: `rotate(${30 * i}deg) translate(120%)`,
            }}
            className="animate-spinner"
          />
        ))}
      </div>
    </div>
  );
}
