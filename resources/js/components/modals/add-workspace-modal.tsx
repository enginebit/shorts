/**
 * Add Workspace Modal Component
 *
 * Dub.co Reference: /apps/web/ui/modals/add-workspace-modal.tsx
 *
 * Key Patterns Adopted:
 * - Modal with logo and descriptive header
 * - Workspace creation form with validation
 * - Success handling with navigation
 * - URL parameter cleanup on close
 * - Integration with workspace context
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia useForm for form handling
 * - Replaced Next.js router with Inertia router
 * - Uses our Modal and Dialog components
 * - Integrated with Laravel backend API
 * - Maintains exact visual consistency
 */

import { useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { Modal, Dialog, Button, Input, Label } from '@/components/ui';
import { useAddWorkspaceModal } from '@/contexts/modal-context';
import { useWorkspace } from '@/contexts/workspace-context';
import slugify from 'slugify';

interface CreateWorkspaceFormData {
  name: string;
  slug: string;
  logo?: string;
}

function CreateWorkspaceForm({
  onSuccess,
  className,
}: {
  onSuccess?: (data: CreateWorkspaceFormData) => void;
  className?: string;
}) {
  const { data, setData, post, processing, errors, reset } = useForm<CreateWorkspaceFormData>({
    name: '',
    slug: '',
    logo: '',
  });

  // Auto-generate slug from name
  useEffect(() => {
    if (data.name && !data.slug) {
      const generatedSlug = slugify(data.name, {
        lower: true,
        strict: true,
        remove: /[*+~.()'"!:@]/g,
      });
      setData('slug', generatedSlug);
    }
  }, [data.name, data.slug, setData]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    post(route('workspaces.store'), {
      onSuccess: (response) => {
        toast.success('Successfully created workspace!');
        reset();
        onSuccess?.(data);
      },
      onError: (errors) => {
        if (errors.slug) {
          toast.error(errors.slug);
        } else if (errors.name) {
          toast.error(errors.name);
        } else {
          toast.error('Failed to create workspace. Please try again.');
        }
      },
    });
  };

  return (
    <form onSubmit={handleSubmit} className={cn('space-y-4', className)}>
      <div className="space-y-2">
        <Label htmlFor="workspace-name">Workspace Name</Label>
        <Input
          id="workspace-name"
          type="text"
          placeholder="Acme Inc."
          value={data.name}
          onChange={(e) => setData('name', e.target.value)}
          error={errors.name}
          required
        />
        {errors.name && (
          <p className="text-sm text-red-600">{errors.name}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="workspace-slug">Workspace Slug</Label>
        <div className="flex items-center">
          <span className="inline-flex items-center rounded-l-md border border-r-0 border-neutral-300 bg-neutral-50 px-3 py-2 text-sm text-neutral-500">
            {window.location.origin}/
          </span>
          <Input
            id="workspace-slug"
            type="text"
            placeholder="acme"
            value={data.slug}
            onChange={(e) => setData('slug', e.target.value)}
            className="rounded-l-none"
            error={errors.slug}
            required
          />
        </div>
        {errors.slug && (
          <p className="text-sm text-red-600">{errors.slug}</p>
        )}
        <p className="text-xs text-neutral-500">
          This will be your workspace URL. Choose something short and memorable.
        </p>
      </div>

      <div className="space-y-2">
        <Label htmlFor="workspace-logo">Workspace Logo (Optional)</Label>
        <Input
          id="workspace-logo"
          type="url"
          placeholder="https://example.com/logo.png"
          value={data.logo}
          onChange={(e) => setData('logo', e.target.value)}
          error={errors.logo}
        />
        {errors.logo && (
          <p className="text-sm text-red-600">{errors.logo}</p>
        )}
        <p className="text-xs text-neutral-500">
          Optional: Add a logo URL for your workspace.
        </p>
      </div>

      <div className="flex justify-end space-x-3 pt-4">
        <Button
          type="button"
          variant="secondary"
          onClick={() => reset()}
          disabled={processing}
        >
          Cancel
        </Button>
        <Button
          type="submit"
          loading={processing}
          disabled={!data.name || !data.slug}
        >
          Create Workspace
        </Button>
      </div>
    </form>
  );
}

export function AddWorkspaceModal() {
  const { showAddWorkspaceModal, setShowAddWorkspaceModal } = useAddWorkspaceModal();
  const { switchWorkspace } = useWorkspace();

  const handleSuccess = (data: CreateWorkspaceFormData) => {
    // Switch to the new workspace
    switchWorkspace(data.slug);
    setShowAddWorkspaceModal(false);
  };

  const handleClose = () => {
    setShowAddWorkspaceModal(false);
    
    // Clean up URL parameters if they exist
    const url = new URL(window.location.href);
    if (url.searchParams.has('newWorkspace')) {
      url.searchParams.delete('newWorkspace');
      window.history.replaceState({}, '', url.toString());
    }
  };

  return (
    <Modal
      showModal={showAddWorkspaceModal}
      setShowModal={setShowAddWorkspaceModal}
      onClose={handleClose}
      className="max-w-lg"
    >
      <Dialog
        title="Create a workspace"
        description="Set up a common space to manage your links with your team."
        headerClassName="text-center"
        contentClassName="bg-neutral-50"
      >
        <div className="mb-6 flex justify-center">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-black">
            <span className="text-lg font-bold text-white">S</span>
          </div>
        </div>
        
        <CreateWorkspaceForm
          onSuccess={handleSuccess}
          className="space-y-6"
        />
        
        <div className="mt-6 text-center">
          <p className="text-xs text-neutral-500">
            Need help?{' '}
            <a
              href="https://dub.co/help/article/what-is-a-workspace"
              target="_blank"
              rel="noopener noreferrer"
              className="font-medium underline decoration-dotted underline-offset-2 transition-colors hover:text-neutral-700"
            >
              Learn more about workspaces
            </a>
          </p>
        </div>
      </Dialog>
    </Modal>
  );
}
