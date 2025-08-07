/**
 * Workspace Context
 *
 * Dub.co Reference: /apps/web/lib/swr/use-workspaces.ts and workspace state management
 *
 * Key Patterns Adopted:
 * - Workspace switching with URL navigation
 * - Current workspace detection from URL params
 * - Workspace data caching and management
 * - User role and permissions handling
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia.js for navigation instead of Next.js router
 * - Workspace data comes from Laravel via Inertia props
 * - No SWR - uses Inertia's built-in data management
 * - Maintains exact dub-main API compatibility
 */

import { createContext, useContext, ReactNode, useMemo } from 'react';
import { usePage, router } from '@inertiajs/react';
import { PageProps } from '@/types';

export interface WorkspaceUser {
  role: 'owner' | 'member';
  defaultFolderId?: string | null;
}

export interface WorkspaceDomain {
  slug: string;
  primary: boolean;
  verified: boolean;
  linkRetentionDays?: number | null;
}

export interface Workspace {
  id: string;
  name: string;
  slug: string;
  logo: string | null;
  plan: 'free' | 'pro' | 'business' | 'business (legacy)' | 'enterprise';
  usage: number;
  usageLimit: number;
  linksUsage: number;
  linksLimit: number;
  domainsLimit: number;
  usersLimit: number;
  conversionEnabled: boolean;
  partnersEnabled: boolean;
  createdAt: string;
  users: WorkspaceUser[];
  domains: WorkspaceDomain[];
  flags?: Record<string, boolean>;
}

interface WorkspaceContextType {
  // Current workspace data
  currentWorkspace: Workspace | null;
  workspaces: Workspace[];
  
  // User permissions in current workspace
  userRole: 'owner' | 'member' | null;
  isOwner: boolean;
  isMember: boolean;
  
  // Workspace actions
  switchWorkspace: (slug: string) => void;
  refreshWorkspaces: () => void;
  
  // Utility functions
  getWorkspaceBySlug: (slug: string) => Workspace | undefined;
  canPerformAction: (action: string) => boolean;
  
  // Loading states
  isLoading: boolean;
  isSwitching: boolean;
}

const WorkspaceContext = createContext<WorkspaceContextType | null>(null);

interface WorkspaceProviderProps {
  children: ReactNode;
}

export function WorkspaceProvider({ children }: WorkspaceProviderProps) {
  const { props, url } = usePage<PageProps>();
  const { workspaces = [], currentWorkspace = null } = props;

  // Extract current workspace slug from URL
  const currentSlug = useMemo(() => {
    const pathSegments = url.split('/').filter(Boolean);
    // First segment after domain should be workspace slug (if not a reserved route)
    const reservedRoutes = ['login', 'register', 'forgot-password', 'reset-password', 'dashboard'];
    const firstSegment = pathSegments[0];
    
    if (firstSegment && !reservedRoutes.includes(firstSegment)) {
      return firstSegment;
    }
    
    return null;
  }, [url]);

  // Find current workspace
  const workspace = useMemo(() => {
    if (currentWorkspace) {
      return currentWorkspace;
    }
    
    if (currentSlug) {
      return workspaces.find(w => w.slug === currentSlug) || null;
    }
    
    // Fallback to user's default workspace or first workspace
    return workspaces[0] || null;
  }, [currentWorkspace, currentSlug, workspaces]);

  // User role in current workspace
  const userRole = useMemo(() => {
    if (!workspace || !workspace.users.length) {
      return null;
    }
    return workspace.users[0].role;
  }, [workspace]);

  // Permission checks
  const isOwner = userRole === 'owner';
  const isMember = userRole === 'member' || isOwner;

  // Workspace switching
  const switchWorkspace = (slug: string) => {
    const targetWorkspace = workspaces.find(w => w.slug === slug);
    if (!targetWorkspace) {
      console.error('Workspace not found:', slug);
      return;
    }

    // Navigate to workspace dashboard
    router.visit(`/${slug}`, {
      preserveState: false,
      preserveScroll: false,
    });
  };

  // Refresh workspaces data
  const refreshWorkspaces = () => {
    router.reload({
      only: ['workspaces', 'currentWorkspace'],
    });
  };

  // Get workspace by slug
  const getWorkspaceBySlug = (slug: string) => {
    return workspaces.find(w => w.slug === slug);
  };

  // Permission checks for actions
  const canPerformAction = (action: string): boolean => {
    if (!workspace || !isMember) {
      return false;
    }

    switch (action) {
      case 'create_link':
        return workspace.linksUsage < workspace.linksLimit;
      case 'invite_user':
        return isOwner && workspace.users.length < workspace.usersLimit;
      case 'add_domain':
        return isOwner && workspace.domains.length < workspace.domainsLimit;
      case 'manage_settings':
        return isOwner;
      case 'manage_billing':
        return isOwner;
      case 'delete_workspace':
        return isOwner;
      default:
        return isMember;
    }
  };

  const contextValue: WorkspaceContextType = {
    currentWorkspace: workspace,
    workspaces,
    userRole,
    isOwner,
    isMember,
    switchWorkspace,
    refreshWorkspaces,
    getWorkspaceBySlug,
    canPerformAction,
    isLoading: false, // Inertia handles loading states
    isSwitching: false, // Could be enhanced with router state
  };

  return (
    <WorkspaceContext.Provider value={contextValue}>
      {children}
    </WorkspaceContext.Provider>
  );
}

export function useWorkspace(): WorkspaceContextType {
  const context = useContext(WorkspaceContext);
  if (!context) {
    throw new Error('useWorkspace must be used within a WorkspaceProvider');
  }
  return context;
}

// Convenience hooks for common use cases
export function useCurrentWorkspace(): Workspace | null {
  const { currentWorkspace } = useWorkspace();
  return currentWorkspace;
}

export function useWorkspaces(): Workspace[] {
  const { workspaces } = useWorkspace();
  return workspaces;
}

export function useWorkspacePermissions() {
  const { userRole, isOwner, isMember, canPerformAction } = useWorkspace();
  return { userRole, isOwner, isMember, canPerformAction };
}
