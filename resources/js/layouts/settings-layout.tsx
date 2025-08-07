/**
 * SettingsLayout Component
 *
 * Dub.co Reference: /apps/web/ui/layout/settings-layout.tsx
 *
 * Key Patterns Adopted:
 * - Uses PageContent for consistent header layout
 * - MaxWidthWrapper for content constraint
 * - Grid layout with gap-5 for settings sections
 * - Minimum height calculation for full viewport
 * - Proper padding and spacing
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses our PageContent component instead of PageContentOld
 * - Uses our MaxWidthWrapper component
 * - Maintains exact visual consistency with dub-main
 * - Added proper TypeScript interfaces
 * - Compatible with our layout system
 */

import { PropsWithChildren } from 'react';
import { PageContent } from '@/components/layout/page-content';
import { MaxWidthWrapper } from '@/components/layout/max-width-wrapper';

export default function SettingsLayout({ children }: PropsWithChildren) {
  return (
    <PageContent>
      <div className="relative min-h-[calc(100vh-16px)]">
        <MaxWidthWrapper className="grid grid-cols-1 gap-5 pb-10 pt-3">
          {children}
        </MaxWidthWrapper>
      </div>
    </PageContent>
  );
}
