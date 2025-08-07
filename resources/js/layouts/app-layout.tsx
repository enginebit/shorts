/**
 * AppLayout Component
 *
 * Enhanced application layout with navigation system from dub-main migration
 * Features sidebar navigation, workspace switching, user management, and modal system
 */

import { ReactNode } from 'react';
import { MainNav, Sidebar } from '@/components/ui';
import { ModalProvider } from '@/contexts/modal-context';
import { WorkspaceProvider } from '@/contexts/workspace-context';
import { ModalProviderIntegration } from '@/components/modals/modal-provider-integration';

interface AppLayoutProps {
  children: ReactNode;
  className?: string;
}

export default function AppLayout({ children, className }: AppLayoutProps) {
  return (
    <WorkspaceProvider>
      <ModalProvider>
        <MainNav sidebar={Sidebar}>
          <div className={className}>
            {children}
          </div>
        </MainNav>
        
        {/* Modal Components - Rendered at app level */}
        <ModalProviderIntegration />
      </ModalProvider>
    </WorkspaceProvider>
  );
}
