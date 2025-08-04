/**
 * PageWidthWrapper Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/layout/page-width-wrapper.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced @dub/utils with local utils
 * - Maintained exact visual consistency with dub-main
 * - No functional changes needed
 */

import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

export function PageWidthWrapper({
  className,
  children,
}: {
  className?: string;
  children: ReactNode;
}) {
  return (
    <div
      className={cn(
        '@container/page mx-auto w-full max-w-screen-xl px-3 lg:px-6',
        className,
      )}
    >
      {children}
    </div>
  );
}
