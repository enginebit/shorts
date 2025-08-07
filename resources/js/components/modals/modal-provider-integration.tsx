/**
 * Modal Provider Integration Component
 *
 * This component provides all modal components that should be rendered
 * at the app level to ensure they work across all pages.
 *
 * Usage: Include this in your main app layout or root component
 */

import { AddWorkspaceModal } from './add-workspace-modal';
import { ConfirmModal } from './confirm-modal';
import { LinkBuilderModal } from './link-builder-modal';
import { EnhancedLinkBuilderModal } from './link-builder-modal-enhanced';

export function ModalProviderIntegration() {
  return (
    <>
      {/* Workspace Management Modals */}
      <AddWorkspaceModal />
      
      {/* Link Management Modals */}
      <LinkBuilderModal />
      <EnhancedLinkBuilderModal />
      
      {/* Generic Modals */}
      <ConfirmModal />
      
      {/* Additional modals can be added here as they are implemented */}
    </>
  );
}
