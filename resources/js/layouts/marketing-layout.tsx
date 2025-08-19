/**
 * Marketing Layout Component
 *
 * Layout for public/marketing pages with Protocol design background
 * Features sophisticated gradient background with navigation and footer
 */

import { ReactNode } from 'react';
import { ProtocolPage } from '@/components/shared/protocol-background';

interface MarketingLayoutProps {
  children: ReactNode;
  className?: string;
  variant?: 'full' | 'subtle';
}

export default function MarketingLayout({ 
  children, 
  className = '',
  variant = 'subtle'
}: MarketingLayoutProps) {
  return (
    <ProtocolPage variant={variant} className={className}>
      <div className="min-h-screen">
        {/* Navigation could be added here in the future */}
        
        <main className="relative z-10">
          {children}
        </main>
        
        {/* Footer could be added here in the future */}
      </div>
    </ProtocolPage>
  );
}
