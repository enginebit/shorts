/**
 * User Dropdown Component
 *
 * Dub.co Reference: /apps/web/ui/layout/sidebar/user-dropdown.tsx
 *
 * Key Patterns Adopted:
 * - Popover-based dropdown with proper positioning
 * - User avatar with initials fallback
 * - Structured menu with user info section
 * - Proper logout handling with loading state
 * - Responsive mobile behavior
 *
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js signOut with Inertia logout
 * - Replaced Next.js useSession with Laravel auth user
 * - Replaced Next.js Link with Inertia Link
 * - Uses our Popover component implementation
 * - Maintained exact visual consistency and behavior
 */

import { useState } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { User, LogOut, Settings, Gift } from 'lucide-react';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { Popover } from '@/components/ui';
import { route } from 'ziggy-js';

export function UserDropdown() {
  const { auth } = usePage<PageProps>().props;
  const user = auth.user;
  const [openPopover, setOpenPopover] = useState(false);
  const { post, processing } = useForm();

  const handleLogout = () => {
    post(route('logout'));
  };

  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map(word => word[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  if (!user) {
    return (
      <div className="flex size-11 items-center justify-center rounded-lg">
        <div className="size-8 animate-pulse rounded-full bg-neutral-200" />
      </div>
    );
  }

  return (
    <Popover
      openPopover={openPopover}
      setOpenPopover={setOpenPopover}
      content={
        <div className="flex w-full flex-col space-y-px rounded-md bg-white p-2 sm:min-w-56">
          {/* User Info */}
          <div className="p-2">
            <p className="truncate text-sm font-medium text-neutral-900">
              {user.name || user.email?.split('@')[0]}
            </p>
            <p className="truncate text-sm text-neutral-500">
              {user.email}
            </p>
          </div>

          {/* Menu Options */}
          <div className="border-t border-neutral-200 pt-2">
            <Link
              href="/account/settings"
              className="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-neutral-700 transition-colors hover:bg-neutral-100"
              onClick={() => setOpenPopover(false)}
            >
              <Settings className="size-4" />
              Account Settings
            </Link>

            <Link
              href="/profile"
              className="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-neutral-700 transition-colors hover:bg-neutral-100"
              onClick={() => setOpenPopover(false)}
            >
              <User className="size-4" />
              Profile
            </Link>

            <Link
              href="/referrals"
              className="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-neutral-700 transition-colors hover:bg-neutral-100"
              onClick={() => setOpenPopover(false)}
            >
              <Gift className="size-4" />
              Refer and Earn
            </Link>

            <div className="border-t border-neutral-200 pt-2 mt-2">
              <button
                type="button"
                className="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-neutral-700 transition-colors hover:bg-neutral-100"
                onClick={handleLogout}
                disabled={processing}
              >
                <LogOut className="size-4" />
                {processing ? 'Logging out...' : 'Log out'}
              </button>
            </div>
          </div>
        </div>
      }
      align="start"
      side="top"
    >
      <button
        className={cn(
          'group relative flex size-11 items-center justify-center rounded-lg transition-all',
          'hover:bg-neutral-100 active:bg-neutral-200 transition-colors duration-150',
          'outline-none focus-visible:ring-2 focus-visible:ring-neutral-500',
          openPopover && 'bg-neutral-100'
        )}
      >
        <div className="flex size-8 items-center justify-center rounded-full bg-neutral-900 text-sm font-medium text-white">
          {getInitials(user.name)}
        </div>
      </button>
    </Popover>
  );
}
