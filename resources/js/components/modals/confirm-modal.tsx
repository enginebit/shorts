/**
 * Confirm Modal Component
 *
 * Dub.co Reference: /apps/web/ui/modals/confirm-modal.tsx
 *
 * Key Patterns Adopted:
 * - Generic confirmation dialog for destructive actions
 * - Configurable title, description, and button text
 * - Support for danger and default variants
 * - Async action handling with loading states
 * - Keyboard shortcuts (Enter to confirm, Escape to cancel)
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses our Modal and Dialog components
 * - Integrated with modal context system
 * - Maintains exact visual consistency
 * - Supports both sync and async confirmation actions
 */

import { useState, useEffect } from 'react';
import { AlertTriangle, Info } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Modal, Dialog, Button } from '@/components/ui';
import { useConfirmModal } from '@/contexts/modal-context';

export function ConfirmModal() {
  const { showConfirmModal, confirmModalProps, hideConfirm } = useConfirmModal();
  const [isLoading, setIsLoading] = useState(false);

  // Handle keyboard shortcuts
  useEffect(() => {
    if (!showConfirmModal || !confirmModalProps) return;

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Enter' && !isLoading) {
        event.preventDefault();
        handleConfirm();
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [showConfirmModal, confirmModalProps, isLoading]);

  const handleConfirm = async () => {
    if (!confirmModalProps || isLoading) return;

    try {
      setIsLoading(true);
      await confirmModalProps.onConfirm();
      hideConfirm();
    } catch (error) {
      console.error('Confirmation action failed:', error);
      // Keep modal open on error so user can retry
    } finally {
      setIsLoading(false);
    }
  };

  const handleCancel = () => {
    if (isLoading) return;
    
    confirmModalProps?.onCancel?.();
    hideConfirm();
  };

  if (!confirmModalProps) return null;

  const {
    title,
    description,
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    variant = 'default',
  } = confirmModalProps;

  const isDanger = variant === 'danger';

  return (
    <Modal
      showModal={showConfirmModal}
      setShowModal={() => !isLoading && hideConfirm()}
      onClose={handleCancel}
      className="max-w-md"
      preventDefaultClose={isLoading}
    >
      <Dialog
        className="text-center"
        headerClassName="border-none pb-2"
        contentClassName="pb-2"
        footerClassName="border-none pt-2"
        footer={
          <div className="flex justify-center space-x-3">
            <Button
              type="button"
              variant="secondary"
              onClick={handleCancel}
              disabled={isLoading}
              className="min-w-[80px]"
            >
              {cancelText}
            </Button>
            <Button
              type="button"
              variant={isDanger ? 'danger' : 'primary'}
              onClick={handleConfirm}
              loading={isLoading}
              className="min-w-[80px]"
            >
              {confirmText}
            </Button>
          </div>
        }
      >
        <div className="flex flex-col items-center space-y-4">
          {/* Icon */}
          <div
            className={cn(
              'flex h-12 w-12 items-center justify-center rounded-full',
              isDanger
                ? 'bg-red-100 text-red-600'
                : 'bg-blue-100 text-blue-600'
            )}
          >
            {isDanger ? (
              <AlertTriangle className="h-6 w-6" />
            ) : (
              <Info className="h-6 w-6" />
            )}
          </div>

          {/* Title */}
          <h3 className="text-lg font-semibold text-neutral-900">
            {title}
          </h3>

          {/* Description */}
          <p className="text-sm text-neutral-600 text-center max-w-sm">
            {description}
          </p>

          {/* Additional warning for danger actions */}
          {isDanger && (
            <div className="rounded-md bg-red-50 p-3 w-full">
              <p className="text-xs text-red-800 text-center">
                <strong>Warning:</strong> This action cannot be undone.
              </p>
            </div>
          )}
        </div>
      </Dialog>
    </Modal>
  );
}

// Utility function to show confirmation dialogs
export function showConfirmation({
  title,
  description,
  confirmText,
  cancelText,
  variant,
  onConfirm,
  onCancel,
}: {
  title: string;
  description: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'danger' | 'default';
  onConfirm: () => void | Promise<void>;
  onCancel?: () => void;
}) {
  // This would typically be called from a hook or context
  // For now, it's a placeholder for the pattern
  console.log('showConfirmation called with:', {
    title,
    description,
    confirmText,
    cancelText,
    variant,
  });
}

// Example usage:
/*
const { showConfirm } = useConfirmModal();

const handleDelete = () => {
  showConfirm({
    title: 'Delete Link',
    description: 'Are you sure you want to delete this link? This action cannot be undone.',
    confirmText: 'Delete',
    cancelText: 'Cancel',
    variant: 'danger',
    onConfirm: async () => {
      await deleteLink(linkId);
      toast.success('Link deleted successfully');
    },
  });
};
*/
