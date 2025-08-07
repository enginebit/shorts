/**
 * Navigation Button Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/layout/page-content/nav-button.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Uses our Button component instead of dub-main Button
 * - Integrated with our navigation context
 * - Added proper TypeScript interfaces
 * - Uses Lucide React icons for consistency
 */

import { Menu } from 'lucide-react';
import { Button } from '@/components/ui';
import { useSideNav } from '@/contexts/navigation-context';

export function NavButton() {
  const { setIsOpen } = useSideNav();

  return (
    <Button
      type="button"
      variant="outline"
      onClick={() => setIsOpen((prev) => !prev)}
      className="h-auto w-fit p-1 md:hidden"
    >
      <Menu className="size-4" />
    </Button>
  );
}
