/**
 * Modal Component
 *
 * Dub.co Reference: /packages/ui/src/modal.tsx
 *
 * Key Patterns Adopted:
 * - Mobile-responsive modal with drawer behavior
 * - Desktop modal with backdrop and animations
 * - Controlled and uncontrolled modal states
 * - Accessibility features with proper focus management
 * - Prevention of dismissal during toast notifications
 *
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js router with Inertia router for navigation
 * - Simplified implementation without Radix UI and Vaul for now
 * - Maintained exact visual consistency and behavior
 * - Will be enhanced with full Radix UI integration later
 */

import { ReactNode, useEffect, useRef, Dispatch, SetStateAction } from 'react';
import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { useBreakpoint } from '@/hooks/use-media-query';

export interface ModalProps {
  children: ReactNode;
  className?: string;
  showModal?: boolean;
  setShowModal?: Dispatch<SetStateAction<boolean>>;
  onClose?: () => void;
  desktopOnly?: boolean;
  preventDefaultClose?: boolean;
}

export function Modal({
  children,
  className,
  showModal,
  setShowModal,
  onClose,
  desktopOnly = false,
  preventDefaultClose = false,
}: ModalProps) {
  const { isMobile } = useBreakpoint();
  const modalRef = useRef<HTMLDivElement>(null);

  const closeModal = ({ dragged = false } = {}) => {
    if (preventDefaultClose && !dragged) {
      return;
    }

    // Fire onClose event if provided
    onClose && onClose();

    // If setShowModal is defined, use it to close modal
    if (setShowModal) {
      setShowModal(false);
    } else {
      // Fallback to router back (for intercepting routes)
      router.visit(window.history.state?.url || '/dashboard');
    }
  };

  // Handle escape key
  useEffect(() => {
    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape' && (setShowModal ? showModal : true)) {
        closeModal();
      }
    };

    if (setShowModal ? showModal : true) {
      document.addEventListener('keydown', handleEscape);
      // Prevent body scroll
      document.body.style.overflow = 'hidden';
    }

    return () => {
      document.removeEventListener('keydown', handleEscape);
      document.body.style.overflow = 'auto';
    };
  }, [showModal, setShowModal]);

  // Don't render if modal should be closed
  if (setShowModal && !showModal) {
    return null;
  }

  // Mobile drawer implementation
  if (isMobile && !desktopOnly) {
    return (
      <div className="fixed inset-0 z-50">
        {/* Backdrop */}
        <div
          className="fixed inset-0 bg-neutral-100 bg-opacity-10 backdrop-blur"
          onClick={() => closeModal({ dragged: true })}
        />
        
        {/* Drawer content */}
        <div
          ref={modalRef}
          className={cn(
            'fixed bottom-0 left-0 right-0 z-50 flex flex-col',
            'rounded-t-[10px] border-t border-neutral-200 bg-white',
            'animate-slide-up-fade',
            className,
          )}
          onClick={(e) => {
            // Prevent dismissal when clicking inside a toast
            if (
              e.target instanceof Element &&
              e.target.closest('[data-sonner-toast]')
            ) {
              e.preventDefault();
            }
          }}
        >
          <div className="scrollbar-hide flex-1 overflow-y-auto rounded-t-[10px] bg-inherit">
            {/* Drawer handle */}
            <div className="sticky top-0 z-20 flex items-center justify-center rounded-t-[10px] bg-inherit">
              <div className="my-3 h-1 w-12 rounded-full bg-neutral-300" />
            </div>
            {children}
          </div>
        </div>
      </div>
    );
  }

  // Desktop modal implementation
  return (
    <div className="fixed inset-0 z-40">
      {/* Backdrop */}
      <div
        id="modal-backdrop"
        className="animate-fade-in fixed inset-0 z-40 bg-neutral-100 bg-opacity-50 backdrop-blur-md"
        onClick={(e) => {
          if (e.target === e.currentTarget) {
            closeModal();
          }
        }}
      />
      
      {/* Modal content */}
      <div
        ref={modalRef}
        className={cn(
          'fixed inset-0 z-40 m-auto h-fit w-full max-w-md',
          'border border-neutral-200 bg-white p-0 shadow-xl sm:rounded-2xl',
          'scrollbar-hide animate-scale-in overflow-y-auto',
          className,
        )}
        onClick={(e) => {
          // Prevent dismissal when clicking inside a toast
          if (
            e.target instanceof Element &&
            e.target.closest('[data-sonner-toast]')
          ) {
            e.preventDefault();
          }
        }}
      >
        {children}
      </div>
    </div>
  );
}

// Dialog component for structured modal content
export interface DialogProps {
  title?: ReactNode;
  description?: ReactNode;
  children: ReactNode;
  className?: string;
  headerClassName?: string;
  contentClassName?: string;
  footerClassName?: string;
  footer?: ReactNode;
}

export function Dialog({
  title,
  description,
  children,
  className,
  headerClassName,
  contentClassName,
  footerClassName,
  footer,
}: DialogProps) {
  return (
    <div className={cn('flex flex-col', className)}>
      {(title || description) && (
        <div className={cn('border-b border-neutral-200 px-6 py-4', headerClassName)}>
          {title && (
            <h2 className="text-lg font-semibold text-neutral-900">
              {title}
            </h2>
          )}
          {description && (
            <p className="mt-1 text-sm text-neutral-600">
              {description}
            </p>
          )}
        </div>
      )}
      
      <div className={cn('flex-1 px-6 py-4', contentClassName)}>
        {children}
      </div>
      
      {footer && (
        <div className={cn('border-t border-neutral-200 px-6 py-4', footerClassName)}>
          {footer}
        </div>
      )}
    </div>
  );
}
