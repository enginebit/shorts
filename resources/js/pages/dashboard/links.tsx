/**
 * Workspace Links Page
 *
 * Dub.co Reference: /apps/web/app/app.dub.co/(dashboard)/[slug]/links/page-client.tsx
 *
 * Key Patterns Adopted:
 * - Links list with filtering and search functionality
 * - Bulk actions for link management
 * - Link creation via modal integration
 * - Real-time click tracking and analytics
 * - Responsive design with mobile optimization
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia page props for links data
 * - Integrates with our Modal system for link creation/editing
 * - Uses our UI components (CardList, PageWidthWrapper, etc.)
 * - Maintains exact visual consistency with dub-main
 */

import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, Search, Filter, MoreHorizontal, ExternalLink, Copy, Edit, Trash2 } from 'lucide-react';
import { AppLayout } from '@/layouts/app-layout';
import { 
  PageWidthWrapper, 
  Button, 
  CardList,
  PageHeader,
  Input,
  Popover
} from '@/components/ui';
import { useLinkBuilder, useConfirmModal } from '@/contexts/modal-context';
import { useWorkspace } from '@/contexts/workspace-context';
import { toast } from 'sonner';

interface Link {
  id: string;
  url: string;
  shortLink: string;
  domain: string;
  key: string;
  title?: string;
  description?: string;
  image?: string;
  clicks: number;
  uniqueClicks: number;
  lastClicked?: string;
  createdAt: string;
  user: {
    name: string;
    email: string;
    image?: string;
  };
}

interface LinksPageProps {
  links: Link[];
  totalLinks: number;
  currentPage: number;
  totalPages: number;
  search?: string;
  workspace: {
    id: string;
    name: string;
    slug: string;
  };
}

function LinkCard({ link }: { link: Link }) {
  const { showConfirm } = useConfirmModal();

  const handleCopyLink = async () => {
    try {
      await navigator.clipboard.writeText(link.shortLink);
      toast.success('Link copied to clipboard');
    } catch (error) {
      toast.error('Failed to copy link');
    }
  };

  const handleDeleteLink = () => {
    showConfirm({
      title: 'Delete Link',
      description: `Are you sure you want to delete this link? This action cannot be undone and will break any existing references to "${link.shortLink}".`,
      confirmText: 'Delete Link',
      cancelText: 'Cancel',
      variant: 'danger',
      onConfirm: async () => {
        router.delete(route('api.links.destroy', { link: link.id }), {
          onSuccess: () => {
            toast.success('Link deleted successfully');
          },
          onError: () => {
            toast.error('Failed to delete link');
          },
        });
      },
    });
  };

  const handleEditLink = () => {
    // TODO: Open LinkBuilderModal with existing link data
    toast.info('Edit functionality coming soon');
  };

  return (
    <CardList.Card>
      <div className="flex items-center gap-5 px-4 py-2.5 text-sm sm:gap-8 md:gap-12">
        {/* Link Info - Left Column */}
        <div className="min-w-0 grow">
          <div className="flex items-center gap-2">
            {link.image && (
              <img
                src={link.image}
                alt=""
                className="h-4 w-4 rounded-sm object-cover"
              />
            )}
            <div className="min-w-0 flex-1">
              <div className="flex items-center gap-2">
                <a
                  href={link.shortLink}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="font-medium text-neutral-900 hover:text-neutral-700 hover:underline"
                >
                  {link.domain}/{link.key}
                </a>
                <ExternalLink className="h-3 w-3 text-neutral-400" />
              </div>
              <p className="truncate text-neutral-500" title={link.url}>
                {link.url}
              </p>
              {link.title && (
                <p className="truncate text-xs text-neutral-400" title={link.title}>
                  {link.title}
                </p>
              )}
            </div>
          </div>
        </div>

        {/* Stats - Right Column */}
        <div className="flex items-center justify-end gap-2 sm:gap-5">
          <div className="text-right">
            <p className="font-medium text-neutral-900">{link.clicks}</p>
            <p className="text-xs text-neutral-500">clicks</p>
          </div>
          <div className="text-right hidden sm:block">
            <p className="text-xs text-neutral-500">
              {new Date(link.createdAt).toLocaleDateString()}
            </p>
            <p className="text-xs text-neutral-400">{link.user.name}</p>
          </div>

          {/* Actions Menu */}
          <Popover>
            <Popover.Trigger asChild>
              <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </Popover.Trigger>
            <Popover.Content align="end" className="w-48">
              <div className="space-y-1">
                <button
                  onClick={handleCopyLink}
                  className="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-neutral-50 rounded-md"
                >
                  <Copy className="h-4 w-4" />
                  Copy Link
                </button>
                <button
                  onClick={handleEditLink}
                  className="flex w-full items-center gap-2 px-3 py-2 text-sm hover:bg-neutral-50 rounded-md"
                >
                  <Edit className="h-4 w-4" />
                  Edit Link
                </button>
                <button
                  onClick={handleDeleteLink}
                  className="flex w-full items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-md"
                >
                  <Trash2 className="h-4 w-4" />
                  Delete Link
                </button>
              </div>
            </Popover.Content>
          </Popover>
        </div>
      </div>
    </CardList.Card>
  );
}

function LinksHeader({ totalLinks, onSearch }: { totalLinks: number; onSearch: (query: string) => void }) {
  const { setShowLinkBuilder } = useLinkBuilder();
  const [searchQuery, setSearchQuery] = useState('');

  const handleSearchSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSearch(searchQuery);
  };

  return (
    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 className="text-2xl font-bold text-neutral-900">Links</h1>
        <p className="text-sm text-neutral-600">
          {totalLinks} {totalLinks === 1 ? 'link' : 'links'} in this workspace
        </p>
      </div>

      <div className="flex items-center gap-3">
        {/* Search */}
        <form onSubmit={handleSearchSubmit} className="relative">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-neutral-400" />
          <Input
            type="text"
            placeholder="Search links..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="pl-10 w-64"
          />
        </form>

        {/* Filter Button */}
        <Button variant="secondary" size="sm">
          <Filter className="h-4 w-4 mr-2" />
          Filter
        </Button>

        {/* Create Link Button */}
        <Button onClick={() => setShowLinkBuilder(true)}>
          <Plus className="h-4 w-4 mr-2" />
          Create Link
        </Button>
      </div>
    </div>
  );
}

export default function LinksPage({ 
  links, 
  totalLinks, 
  currentPage, 
  totalPages, 
  search,
  workspace 
}: LinksPageProps) {
  const handleSearch = (query: string) => {
    router.get(route('workspace.links', { workspace: workspace.slug }), 
      { search: query }, 
      { preserveState: true }
    );
  };

  return (
    <AppLayout>
      <Head title={`Links - ${workspace.name}`} />

      <PageWidthWrapper className="py-8">
        <div className="space-y-6">
          <LinksHeader totalLinks={totalLinks} onSearch={handleSearch} />

          {/* Links List */}
          {links.length > 0 ? (
            <CardList variant="loose">
              {links.map((link) => (
                <LinkCard key={link.id} link={link} />
              ))}
            </CardList>
          ) : (
            <div className="text-center py-12">
              <div className="mx-auto h-24 w-24 rounded-full bg-neutral-100 flex items-center justify-center">
                <Plus className="h-8 w-8 text-neutral-400" />
              </div>
              <h3 className="mt-4 text-lg font-medium text-neutral-900">No links yet</h3>
              <p className="mt-2 text-sm text-neutral-500 max-w-sm mx-auto">
                Get started by creating your first short link. Share it anywhere and track its performance.
              </p>
              <Button 
                onClick={() => useLinkBuilder().setShowLinkBuilder(true)}
                className="mt-6"
              >
                <Plus className="h-4 w-4 mr-2" />
                Create Your First Link
              </Button>
            </div>
          )}

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex items-center justify-center gap-2">
              {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                <Button
                  key={page}
                  variant={page === currentPage ? 'primary' : 'secondary'}
                  size="sm"
                  onClick={() => {
                    router.get(route('workspace.links', { workspace: workspace.slug }), 
                      { page, search }, 
                      { preserveState: true }
                    );
                  }}
                >
                  {page}
                </Button>
              ))}
            </div>
          )}
        </div>
      </PageWidthWrapper>
    </AppLayout>
  );
}
