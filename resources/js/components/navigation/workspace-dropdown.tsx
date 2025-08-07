/**
 * Workspace Dropdown Component
 *
 * Dub.co Reference: /apps/web/ui/layout/sidebar/workspace-dropdown.tsx
 *
 * Key Patterns Adopted:
 * - Workspace switching with proper URL navigation
 * - Current workspace detection and highlighting
 * - Workspace avatar with fallback to generated avatar
 * - Plan display with color coding
 * - Settings and invite member quick actions
 * - Create new workspace action
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses our WorkspaceContext instead of useWorkspaces hook
 * - Replaced Next.js Link with Inertia Link
 * - Replaced Next.js router with Inertia router
 * - Uses our Popover component implementation
 * - Maintained exact visual consistency and behavior
 */

import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Settings, Plus, UserPlus, Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useWorkspace } from '@/contexts/workspace-context';
import { Popover } from '@/components/ui';

function WorkspaceDropdownPlaceholder() {
  return (
    <div className="flex size-11 animate-pulse items-center gap-x-1.5 rounded-lg bg-neutral-300" />
  );
}

export function WorkspaceDropdown() {
  const { currentWorkspace, workspaces, switchWorkspace, isOwner } = useWorkspace();
  const [openPopover, setOpenPopover] = useState(false);

  if (!currentWorkspace || !workspaces) {
    return <WorkspaceDropdownPlaceholder />;
  }

  const getWorkspaceImage = (workspaceId: string, logo?: string | null) => {
    return logo || `https://avatar.vercel.sh/${workspaceId}`;
  };

  const getPlanColor = (plan: string) => {
    switch (plan.toLowerCase()) {
      case 'enterprise':
        return 'text-purple-700';
      case 'business':
      case 'business (legacy)':
        return 'text-blue-900';
      case 'pro':
        return 'text-cyan-900';
      default:
        return 'text-neutral-500';
    }
  };

  return (
    <Popover
      openPopover={openPopover}
      setOpenPopover={setOpenPopover}
      content={
        <div className="flex w-full flex-col space-y-px rounded-md bg-white p-2 sm:min-w-60">
          {/* Current workspace info */}
          <div className="p-2">
            <p className="text-xs font-medium text-neutral-500 uppercase tracking-wide">
              Current workspace
            </p>
          </div>

          {/* Workspace list */}
          <div className="space-y-px">
            {workspaces.map((workspace) => (
              <button
                key={workspace.id}
                className={cn(
                  'flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors',
                  workspace.id === currentWorkspace.id
                    ? 'bg-neutral-100 text-neutral-900'
                    : 'text-neutral-700 hover:bg-neutral-100'
                )}
                onClick={() => {
                  if (workspace.id !== currentWorkspace.id) {
                    switchWorkspace(workspace.slug);
                  }
                  setOpenPopover(false);
                }}
              >
                <img
                  src={getWorkspaceImage(workspace.id, workspace.logo)}
                  alt={workspace.name}
                  className="size-8 rounded-lg"
                />
                <div className="flex-1 text-left">
                  <p className="truncate font-medium">{workspace.name}</p>
                  <p className={cn('truncate text-xs capitalize', getPlanColor(workspace.plan))}>
                    {workspace.plan} plan
                  </p>
                </div>
                {workspace.id === currentWorkspace.id && (
                  <Check className="size-4 text-neutral-600" />
                )}
              </button>
            ))}
          </div>

          {/* Actions */}
          <div className="border-t border-neutral-200 pt-2">
            <Link
              href="/workspaces/new"
              className="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-neutral-700 transition-colors hover:bg-neutral-100"
            >
              <Plus className="size-4" />
              Create workspace
            </Link>

            {isOwner && (
              <Link
                href={`/${currentWorkspace.slug}/settings`}
                className="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-neutral-700 transition-colors hover:bg-neutral-100"
                onClick={() => setOpenPopover(false)}
              >
                <Settings className="size-4" />
                Workspace settings
              </Link>
            )}

            {isOwner && (
              <Link
                href={`/${currentWorkspace.slug}/settings/people`}
                className="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-neutral-700 transition-colors hover:bg-neutral-100"
                onClick={() => setOpenPopover(false)}
              >
                <UserPlus className="size-4" />
                Invite members
              </Link>
            )}
          </div>
        </div>
      }
      align="start"
      side="bottom"
    >
      <button
        className={cn(
          'group relative flex size-11 items-center justify-center rounded-lg transition-all',
          'hover:bg-neutral-100 active:bg-neutral-200 transition-colors duration-150',
          'outline-none focus-visible:ring-2 focus-visible:ring-neutral-500',
          openPopover && 'bg-neutral-100'
        )}
      >
        <img
          src={getWorkspaceImage(currentWorkspace.id, currentWorkspace.logo)}
          alt={currentWorkspace.name}
          className="size-8 rounded-lg"
        />
      </button>
    </Popover>
  );
}
