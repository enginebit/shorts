/**
 * Navigation Context
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/layout/main-nav.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Removed Next.js usePathname, using Inertia's router instead
 * - Maintained exact context structure and behavior
 * - Added TypeScript interfaces for better type safety
 */

import { createContext, useContext } from 'react';

interface SideNavContextType {
  isOpen: boolean;
  setIsOpen: (value: boolean | ((prev: boolean) => boolean)) => void;
}

export const SideNavContext = createContext<SideNavContextType>({
  isOpen: false,
  setIsOpen: () => {},
});

export const useSideNav = () => {
  const context = useContext(SideNavContext);
  if (!context) {
    throw new Error('useSideNav must be used within a SideNavProvider');
  }
  return context;
};
