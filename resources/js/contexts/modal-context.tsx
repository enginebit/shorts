/**
 * Modal Context and Provider
 *
 * Dub.co Reference: /apps/web/ui/modals/modal-provider.tsx
 *
 * Key Patterns Adopted:
 * - Centralized modal state management
 * - Context-based modal control system
 * - Support for multiple concurrent modals
 * - URL parameter-based modal initialization
 * - Modal hooks for each modal type
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia router instead of Next.js router
 * - Replaced Next.js useSearchParams with URL parsing
 * - Maintains exact modal management patterns
 * - Integrated with our workspace and authentication systems
 */

import {
  createContext,
  useContext,
  ReactNode,
  Dispatch,
  SetStateAction,
  useState,
  useEffect,
  useMemo,
} from 'react';
import { usePage } from '@inertiajs/react';
import { getUrlFromString } from '@/lib/utils';

// Modal state interface
export interface ModalContextType {
  // Workspace modals
  showAddWorkspaceModal: boolean;
  setShowAddWorkspaceModal: Dispatch<SetStateAction<boolean>>;
  
  // Domain modals
  showAddEditDomainModal: boolean;
  setShowAddEditDomainModal: Dispatch<SetStateAction<boolean>>;
  
  // Link modals
  showLinkBuilder: boolean;
  setShowLinkBuilder: Dispatch<SetStateAction<boolean>>;
  
  // Tag modals
  showAddEditTagModal: boolean;
  setShowAddEditTagModal: Dispatch<SetStateAction<boolean>>;
  
  // Import modals
  showImportCsvModal: boolean;
  setShowImportCsvModal: Dispatch<SetStateAction<boolean>>;
  
  // Generic modals
  showConfirmModal: boolean;
  setShowConfirmModal: Dispatch<SetStateAction<boolean>>;
  confirmModalProps: ConfirmModalProps | null;
  setConfirmModalProps: Dispatch<SetStateAction<ConfirmModalProps | null>>;
}

export interface ConfirmModalProps {
  title: string;
  description: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'danger' | 'default';
  onConfirm: () => void | Promise<void>;
  onCancel?: () => void;
}

const ModalContext = createContext<ModalContextType | null>(null);

export interface ModalProviderProps {
  children: ReactNode;
}

export function ModalProvider({ children }: ModalProviderProps) {
  const { url } = usePage();
  
  // Parse URL parameters for modal initialization
  const urlParams = useMemo(() => {
    try {
      const urlObj = new URL(window.location.href);
      return urlObj.searchParams;
    } catch {
      return new URLSearchParams();
    }
  }, [url]);

  // Check for new link creation from URL parameters
  const newLinkValues = useMemo(() => {
    const newLink = urlParams.get('newLink');
    if (newLink && getUrlFromString(newLink)) {
      return {
        url: getUrlFromString(newLink),
        domain: urlParams.get('newLinkDomain'),
      };
    }
    return null;
  }, [urlParams]);

  // Modal states
  const [showAddWorkspaceModal, setShowAddWorkspaceModal] = useState(false);
  const [showAddEditDomainModal, setShowAddEditDomainModal] = useState(false);
  const [showLinkBuilder, setShowLinkBuilder] = useState(false);
  const [showAddEditTagModal, setShowAddEditTagModal] = useState(false);
  const [showImportCsvModal, setShowImportCsvModal] = useState(false);
  const [showConfirmModal, setShowConfirmModal] = useState(false);
  const [confirmModalProps, setConfirmModalProps] = useState<ConfirmModalProps | null>(null);

  // Initialize link builder if URL parameters indicate new link creation
  useEffect(() => {
    if (newLinkValues && !showLinkBuilder) {
      setShowLinkBuilder(true);
    }
  }, [newLinkValues, showLinkBuilder]);

  // Handle URL parameter-based modal opening
  useEffect(() => {
    const invite = urlParams.get('invite');
    const upgrade = urlParams.get('upgrade');
    const welcome = urlParams.get('welcome');

    // Handle invite modal
    if (invite === 'true') {
      // Handle invite acceptance logic here
      console.log('Invite modal should be shown');
    }

    // Handle upgrade modal
    if (upgrade === 'true') {
      // Handle upgrade modal logic here
      console.log('Upgrade modal should be shown');
    }

    // Handle welcome modal
    if (welcome === 'true') {
      // Handle welcome modal logic here
      console.log('Welcome modal should be shown');
    }
  }, [urlParams]);

  const contextValue: ModalContextType = {
    showAddWorkspaceModal,
    setShowAddWorkspaceModal,
    showAddEditDomainModal,
    setShowAddEditDomainModal,
    showLinkBuilder,
    setShowLinkBuilder,
    showAddEditTagModal,
    setShowAddEditTagModal,
    showImportCsvModal,
    setShowImportCsvModal,
    showConfirmModal,
    setShowConfirmModal,
    confirmModalProps,
    setConfirmModalProps,
  };

  return (
    <ModalContext.Provider value={contextValue}>
      {children}
    </ModalContext.Provider>
  );
}

export function useModal(): ModalContextType {
  const context = useContext(ModalContext);
  if (!context) {
    throw new Error('useModal must be used within a ModalProvider');
  }
  return context;
}

// Convenience hooks for specific modals
export function useAddWorkspaceModal() {
  const { showAddWorkspaceModal, setShowAddWorkspaceModal } = useModal();
  return { showAddWorkspaceModal, setShowAddWorkspaceModal };
}

export function useAddEditDomainModal() {
  const { showAddEditDomainModal, setShowAddEditDomainModal } = useModal();
  return { showAddEditDomainModal, setShowAddEditDomainModal };
}

export function useLinkBuilder() {
  const { showLinkBuilder, setShowLinkBuilder } = useModal();
  return { showLinkBuilder, setShowLinkBuilder };
}

export function useAddEditTagModal() {
  const { showAddEditTagModal, setShowAddEditTagModal } = useModal();
  return { showAddEditTagModal, setShowAddEditTagModal };
}

export function useImportCsvModal() {
  const { showImportCsvModal, setShowImportCsvModal } = useModal();
  return { showImportCsvModal, setShowImportCsvModal };
}

export function useConfirmModal() {
  const {
    showConfirmModal,
    setShowConfirmModal,
    confirmModalProps,
    setConfirmModalProps,
  } = useModal();

  const showConfirm = (props: ConfirmModalProps) => {
    setConfirmModalProps(props);
    setShowConfirmModal(true);
  };

  const hideConfirm = () => {
    setShowConfirmModal(false);
    setConfirmModalProps(null);
  };

  return {
    showConfirmModal,
    confirmModalProps,
    showConfirm,
    hideConfirm,
  };
}
