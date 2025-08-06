/**
 * Modal Components Index
 *
 * Centralized exports for all modal components
 * Following dub-main modal organization patterns
 */

// Core modal components
export { Modal, Dialog } from '../ui/modal';

// Specific modal implementations
export { AddWorkspaceModal } from './add-workspace-modal';
export { ConfirmModal } from './confirm-modal';
export { LinkBuilderModal } from './link-builder-modal';

// Modal integration
export { ModalProviderIntegration } from './modal-provider-integration';

// Modal context and hooks
export {
  ModalProvider,
  useModal,
  useAddWorkspaceModal,
  useAddEditDomainModal,
  useLinkBuilder,
  useAddEditTagModal,
  useImportCsvModal,
  useConfirmModal,
} from '../contexts/modal-context';
