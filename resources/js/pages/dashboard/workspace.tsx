/**
 * Workspace Dashboard Page
 *
 * Dub.co Reference: /apps/web/app/app.dub.co/(dashboard)/[slug]/page.tsx
 *
 * Key Patterns Adopted:
 * - Workspace-specific dashboard with statistics and recent links
 * - Quick actions for link creation and management
 * - Analytics overview and workspace metrics
 * - Recent activity and link performance
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia page props for workspace data
 * - Integrates with our Modal system for link creation
 * - Uses our UI components (CardList, PageWidthWrapper, etc.)
 * - Maintains exact visual consistency with dub-main
 */

import { Head } from '@inertiajs/react';
import { Plus, BarChart3, Link as LinkIcon, Users, Globe } from 'lucide-react';
import { AppLayout } from '@/layouts/app-layout';
import { 
  PageWidthWrapper, 
  Button, 
  CardList,
  PageHeader 
} from '@/components/ui';
import { useLinkBuilder } from '@/contexts/modal-context';
import { useWorkspace } from '@/contexts/workspace-context';

interface WorkspaceStats {
  totalLinks: number;
  totalClicks: number;
  linksUsage: number;
  linksLimit: number;
  domainsCount: number;
  membersCount: number;
}

interface RecentLink {
  id: string;
  url: string;
  shortLink: string;
  title?: string;
  description?: string;
  clicks: number;
  createdAt: string;
  user: {
    name: string;
    email: string;
  };
}

interface WorkspaceData {
  id: string;
  name: string;
  slug: string;
  logo?: string;
  plan: string;
  stats: WorkspaceStats;
  recentLinks: RecentLink[];
}

interface WorkspaceDashboardProps {
  workspace: WorkspaceData;
}

function StatsCard({ 
  title, 
  value, 
  icon: Icon, 
  description,
  trend 
}: {
  title: string;
  value: string | number;
  icon: React.ComponentType<{ className?: string }>;
  description?: string;
  trend?: string;
}) {
  return (
    <div className="rounded-lg border border-neutral-200 bg-white p-6">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm font-medium text-neutral-600">{title}</p>
          <p className="text-2xl font-bold text-neutral-900">{value}</p>
          {description && (
            <p className="text-sm text-neutral-500">{description}</p>
          )}
        </div>
        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-neutral-50">
          <Icon className="h-6 w-6 text-neutral-600" />
        </div>
      </div>
      {trend && (
        <div className="mt-4">
          <span className="text-sm text-green-600">{trend}</span>
        </div>
      )}
    </div>
  );
}

function RecentLinksCard({ links }: { links: RecentLink[] }) {
  return (
    <div className="rounded-lg border border-neutral-200 bg-white">
      <div className="border-b border-neutral-200 px-6 py-4">
        <h3 className="text-lg font-semibold text-neutral-900">Recent Links</h3>
        <p className="text-sm text-neutral-600">Your latest shortened links</p>
      </div>
      
      <div className="p-6">
        {links.length > 0 ? (
          <CardList variant="loose">
            {links.map((link) => (
              <CardList.Card key={link.id}>
                <div className="flex items-center justify-between p-4">
                  <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                      <LinkIcon className="h-4 w-4 text-neutral-400" />
                      <p className="truncate text-sm font-medium text-neutral-900">
                        {link.title || link.url}
                      </p>
                    </div>
                    <p className="mt-1 truncate text-sm text-neutral-500">
                      {link.shortLink}
                    </p>
                    {link.description && (
                      <p className="mt-1 truncate text-xs text-neutral-400">
                        {link.description}
                      </p>
                    )}
                  </div>
                  <div className="flex items-center gap-4 text-sm text-neutral-500">
                    <div className="text-right">
                      <p className="font-medium text-neutral-900">{link.clicks}</p>
                      <p className="text-xs">clicks</p>
                    </div>
                    <div className="text-right">
                      <p className="text-xs">
                        {new Date(link.createdAt).toLocaleDateString()}
                      </p>
                      <p className="text-xs">{link.user.name}</p>
                    </div>
                  </div>
                </div>
              </CardList.Card>
            ))}
          </CardList>
        ) : (
          <div className="text-center py-8">
            <LinkIcon className="mx-auto h-12 w-12 text-neutral-400" />
            <h3 className="mt-4 text-sm font-medium text-neutral-900">No links yet</h3>
            <p className="mt-2 text-sm text-neutral-500">
              Get started by creating your first short link.
            </p>
          </div>
        )}
      </div>
    </div>
  );
}

function QuickActions() {
  const { setShowLinkBuilder } = useLinkBuilder();

  return (
    <div className="rounded-lg border border-neutral-200 bg-white p-6">
      <h3 className="text-lg font-semibold text-neutral-900 mb-4">Quick Actions</h3>
      
      <div className="space-y-3">
        <Button
          onClick={() => setShowLinkBuilder(true)}
          className="w-full justify-start"
          variant="secondary"
        >
          <Plus className="h-4 w-4 mr-2" />
          Create New Link
        </Button>
        
        <Button
          onClick={() => window.location.href = `/${window.location.pathname.split('/')[1]}/analytics`}
          className="w-full justify-start"
          variant="secondary"
        >
          <BarChart3 className="h-4 w-4 mr-2" />
          View Analytics
        </Button>
        
        <Button
          onClick={() => window.location.href = `/${window.location.pathname.split('/')[1]}/settings`}
          className="w-full justify-start"
          variant="secondary"
        >
          <Globe className="h-4 w-4 mr-2" />
          Workspace Settings
        </Button>
      </div>
    </div>
  );
}

export default function WorkspaceDashboard({ workspace }: WorkspaceDashboardProps) {
  const { currentWorkspace } = useWorkspace();

  return (
    <AppLayout>
      <Head title={`${workspace.name} - Dashboard`} />

      <PageHeader
        title={workspace.name}
        description="Manage your links and view analytics"
      />

      <PageWidthWrapper className="py-8">
        <div className="grid gap-6">
          {/* Stats Overview */}
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <StatsCard
              title="Total Links"
              value={workspace.stats.totalLinks}
              icon={LinkIcon}
              description={`${workspace.stats.linksUsage}/${workspace.stats.linksLimit} used`}
            />
            <StatsCard
              title="Total Clicks"
              value={workspace.stats.totalClicks.toLocaleString()}
              icon={BarChart3}
              description="All time"
            />
            <StatsCard
              title="Domains"
              value={workspace.stats.domainsCount}
              icon={Globe}
              description="Custom domains"
            />
            <StatsCard
              title="Team Members"
              value={workspace.stats.membersCount}
              icon={Users}
              description="Active members"
            />
          </div>

          {/* Main Content Grid */}
          <div className="grid gap-6 lg:grid-cols-3">
            {/* Recent Links - Takes 2 columns */}
            <div className="lg:col-span-2">
              <RecentLinksCard links={workspace.recentLinks} />
            </div>

            {/* Quick Actions - Takes 1 column */}
            <div>
              <QuickActions />
            </div>
          </div>
        </div>
      </PageWidthWrapper>
    </AppLayout>
  );
}
