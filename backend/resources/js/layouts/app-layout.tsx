/**
 * AppLayout Component
 * 
 * Basic application layout following dub-main patterns
 * Will be enhanced with full navigation and sidebar components later
 */

import { ReactNode } from 'react';
import { PageWidthWrapper } from '@/components/layout/page-width-wrapper';

interface AppLayoutProps {
  children: ReactNode;
  className?: string;
}

export default function AppLayout({ children, className }: AppLayoutProps) {
  return (
    <div className="min-h-screen bg-neutral-50">
      <main className={className}>
        <PageWidthWrapper>
          {children}
        </PageWidthWrapper>
      </main>
    </div>
  );
}
