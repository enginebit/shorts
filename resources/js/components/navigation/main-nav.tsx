/**
 * Main Navigation Component
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/layout/main-nav.tsx
 *
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js usePathname with Inertia router
 * - Maintained exact layout structure and responsive behavior
 * - Added proper TypeScript interfaces
 * - Integrated with our navigation context
 */

import { ReactNode, ComponentType, useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { SideNavContext } from '@/contexts/navigation-context';
import { useBreakpoint } from '@/hooks/use-media-query';

interface MainNavProps {
  children: ReactNode;
  sidebar: ComponentType<{
    toolContent?: ReactNode;
    newsContent?: ReactNode;
  }>;
  toolContent?: ReactNode;
  newsContent?: ReactNode;
}

export function MainNav({
  children,
  sidebar: Sidebar,
  toolContent,
  newsContent,
}: MainNavProps) {
  const { isMobile } = useBreakpoint();
  const [isOpen, setIsOpen] = useState(false);

  // Prevent body scroll when side nav is open
  useEffect(() => {
    document.body.style.overflow = isOpen && isMobile ? "hidden" : "auto";

    return () => {
      document.body.style.overflow = "auto";
    };
  }, [isOpen, isMobile]);

  // Close side nav when route changes
  useEffect(() => {
    const handleStart = () => setIsOpen(false);

    router.on('start', handleStart);

    return () => {
      router.off('start', handleStart);
    };
  }, []);

  return (
    <div className="min-h-screen md:grid md:grid-cols-[min-content_minmax(0,1fr)]">
      {/* Side nav backdrop */}
      <div
        className={cn(
          "fixed left-0 top-0 z-50 h-dvh w-screen transition-[background-color,backdrop-filter] md:sticky md:z-auto md:w-full md:bg-transparent",
          isOpen
            ? "bg-black/20 backdrop-blur-sm"
            : "bg-transparent max-md:pointer-events-none",
        )}
        onClick={(e) => {
          if (e.target === e.currentTarget) {
            e.stopPropagation();
            setIsOpen(false);
          }
        }}
      >
        {/* Side nav */}
        <div
          className={cn(
            "relative h-full w-min max-w-full bg-neutral-200 transition-transform md:translate-x-0",
            !isOpen && "-translate-x-full",
          )}
        >
          <Sidebar toolContent={toolContent} newsContent={newsContent} />
        </div>
      </div>
      <div className="bg-neutral-200 md:pt-2">
        <div className="relative min-h-full bg-neutral-100 pt-px md:rounded-tl-xl md:bg-white">
          <SideNavContext.Provider value={{ isOpen, setIsOpen }}>
            {children}
          </SideNavContext.Provider>
        </div>
      </div>
    </div>
  );
}
