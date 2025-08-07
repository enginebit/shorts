/**
 * MaxWidthWrapper Component
 *
 * Dub.co Reference: /packages/ui/src/max-width-wrapper.tsx
 *
 * Key Patterns Adopted:
 * - Centered layout with mx-auto
 * - Max width constraint (max-w-screen-xl)
 * - Responsive padding (px-3 lg:px-10)
 * - Full width with w-full
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses our tailwind-merge utility (clsx + tailwind-merge)
 * - Maintains exact dub-main styling patterns
 * - Compatible with our Tailwind v4 configuration
 * - Different from PageWidthWrapper with larger padding on desktop
 */

import { cn } from '@/lib/utils';
import { ReactNode } from 'react';

interface MaxWidthWrapperProps {
  className?: string;
  children: ReactNode;
}

export function MaxWidthWrapper({
  className,
  children,
}: MaxWidthWrapperProps) {
  return (
    <div
      className={cn(
        'mx-auto w-full max-w-screen-xl px-3 lg:px-10',
        className,
      )}
    >
      {children}
    </div>
  );
}
