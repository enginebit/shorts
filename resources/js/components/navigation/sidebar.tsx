/**
 * Sidebar Component
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/layout/sidebar/sidebar-nav.tsx
 *
 * Adaptations for Laravel + Inertia.js:
 * - Simplified initial implementation focusing on core navigation
 * - Replaced Next.js Link with Inertia Link
 * - Integrated with our workspace and user dropdown components
 * - Maintained exact visual consistency with dub-main
 * - Added proper TypeScript interfaces
 */

import { ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
  Home,
  Link as LinkIcon,
  BarChart3,
  Globe,
  CreditCard,
  Settings
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { PageProps } from '@/types';
import { UserDropdown } from './user-dropdown';
import { WorkspaceDropdown } from './workspace-dropdown';

interface SidebarProps {
  toolContent?: ReactNode;
  newsContent?: ReactNode;
}

interface NavItem {
  name: string;
  href: string;
  icon: React.ComponentType<{ className?: string }>;
  current?: boolean;
}

const navigation: NavItem[] = [
  { name: 'Dashboard', href: '/dashboard', icon: Home },
  { name: 'Links', href: '/links', icon: LinkIcon },
  { name: 'Analytics', href: '/analytics', icon: BarChart3 },
  { name: 'Domains', href: '/domains', icon: Globe },
  { name: 'Billing', href: '/billing', icon: CreditCard },
  { name: 'Settings', href: '/settings', icon: Settings },
];

export function Sidebar({ toolContent, newsContent }: SidebarProps) {
  const { auth } = usePage<PageProps>().props;
  const { url } = usePage();

  // Mock workspaces data - will be replaced with real data later
  const mockWorkspaces = [
    {
      id: '1',
      name: 'Personal',
      slug: 'personal',
      plan: 'free',
    },
    {
      id: '2',
      name: 'My Company',
      slug: 'my-company',
      plan: 'pro',
    },
  ];

  const currentWorkspace = mockWorkspaces[0]; // Mock current workspace

  return (
    <div className="flex h-full w-80 flex-col bg-white border-r border-gray-200">
      {/* Logo and Workspace Switcher */}
      <div className="flex flex-col gap-4 p-4 border-b border-gray-200">
        {/* Logo */}
        <div className="flex items-center gap-2">
          <div className="flex size-8 items-center justify-center rounded-lg bg-blue-600 text-white font-bold text-sm">
            S
          </div>
          <span className="text-lg font-semibold text-gray-900">Shorts</span>
        </div>

        {/* Workspace Switcher */}
        <WorkspaceDropdown
          workspaces={mockWorkspaces}
          currentWorkspace={currentWorkspace}
          user={auth.user}
        />
      </div>

      {/* Navigation */}
      <nav className="flex-1 px-4 py-4">
        <ul className="space-y-1">
          {navigation.map((item) => {
            const isActive = url.startsWith(item.href);

            return (
              <li key={item.name}>
                <Link
                  href={item.href}
                  className={cn(
                    "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors",
                    isActive
                      ? "bg-blue-50 text-blue-700"
                      : "text-gray-700 hover:bg-gray-100 hover:text-gray-900"
                  )}
                >
                  <item.icon className="size-5" />
                  {item.name}
                </Link>
              </li>
            );
          })}
        </ul>
      </nav>

      {/* Tool Content */}
      {toolContent && (
        <div className="border-t border-neutral-200 p-4">
          {toolContent}
        </div>
      )}

      {/* News Content */}
      {newsContent && (
        <div className="border-t border-neutral-200 p-4">
          {newsContent}
        </div>
      )}

      {/* User Dropdown */}
      <div className="border-t border-neutral-200 p-4">
        <UserDropdown />
      </div>
    </div>
  );
}
