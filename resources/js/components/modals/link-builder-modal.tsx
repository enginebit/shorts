/**
 * Link Builder Modal Component
 *
 * Dub.co Reference: /apps/web/ui/modals/link-builder/index.tsx
 *
 * Key Patterns Adopted:
 * - Comprehensive link creation and editing interface
 * - URL validation and preview
 * - Domain selection with workspace domains
 * - Custom key generation and validation
 * - Advanced options (expiration, password, etc.)
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia useForm for form handling
 * - Integrated with our Modal and Dialog components
 * - Uses Laravel backend for link creation/editing
 * - Maintains exact visual consistency
 * - Simplified initial implementation (will be enhanced)
 */

import { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import { Link as LinkIcon, Globe, Settings } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Modal, Dialog, Button, Input, Label, Select } from '@/components/ui';
import { useLinkBuilder } from '@/contexts/modal-context';
import { useWorkspace } from '@/contexts/workspace-context';

interface LinkFormData {
  url: string;
  domain: string;
  key: string;
  title?: string;
  description?: string;
  image?: string;
  expiresAt?: string;
  password?: string;
  ios?: string;
  android?: string;
  geo?: Record<string, string>;
}

function LinkBuilderForm({
  onSuccess,
  initialData,
}: {
  onSuccess?: (data: any) => void;
  initialData?: Partial<LinkFormData>;
}) {
  const { currentWorkspace } = useWorkspace();
  const [showAdvanced, setShowAdvanced] = useState(false);
  
  const { data, setData, post, put, processing, errors, reset } = useForm<LinkFormData>({
    url: initialData?.url || '',
    domain: initialData?.domain || 'dub.sh', // Default domain
    key: initialData?.key || '',
    title: initialData?.title || '',
    description: initialData?.description || '',
    image: initialData?.image || '',
    expiresAt: initialData?.expiresAt || '',
    password: initialData?.password || '',
    ios: initialData?.ios || '',
    android: initialData?.android || '',
    geo: initialData?.geo || {},
  });

  // Auto-generate key from URL if not provided
  useEffect(() => {
    if (data.url && !data.key) {
      try {
        const urlObj = new URL(data.url);
        const hostname = urlObj.hostname.replace('www.', '');
        const path = urlObj.pathname.split('/').filter(Boolean)[0];
        const suggestedKey = path || hostname.split('.')[0];
        setData('key', suggestedKey.toLowerCase().replace(/[^a-z0-9-]/g, ''));
      } catch {
        // Invalid URL, don't auto-generate key
      }
    }
  }, [data.url, data.key, setData]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    const endpoint = initialData ? route('links.update', { link: 'id' }) : route('links.store');
    const method = initialData ? put : post;
    
    method(endpoint, {
      onSuccess: (response) => {
        toast.success(initialData ? 'Link updated successfully!' : 'Link created successfully!');
        reset();
        onSuccess?.(response);
      },
      onError: (errors) => {
        if (errors.url) {
          toast.error('Please enter a valid URL');
        } else if (errors.key) {
          toast.error('This short link already exists');
        } else {
          toast.error('Failed to save link. Please try again.');
        }
      },
    });
  };

  // Mock domains - will be replaced with real workspace domains
  const availableDomains = [
    { value: 'dub.sh', label: 'dub.sh' },
    { value: 'short.ly', label: 'short.ly' },
    ...(currentWorkspace?.domains?.map(domain => ({
      value: domain.slug,
      label: domain.slug,
    })) || []),
  ];

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {/* Destination URL */}
      <div className="space-y-2">
        <Label htmlFor="destination-url" className="flex items-center gap-2">
          <LinkIcon className="h-4 w-4" />
          Destination URL
        </Label>
        <Input
          id="destination-url"
          type="url"
          placeholder="https://example.com"
          value={data.url}
          onChange={(e) => setData('url', e.target.value)}
          error={errors.url}
          required
        />
        {errors.url && (
          <p className="text-sm text-red-600">{errors.url}</p>
        )}
      </div>

      {/* Short Link */}
      <div className="space-y-2">
        <Label htmlFor="short-link" className="flex items-center gap-2">
          <Globe className="h-4 w-4" />
          Short Link
        </Label>
        <div className="flex items-center space-x-2">
          <Select
            value={data.domain}
            onValueChange={(value) => setData('domain', value)}
            options={availableDomains}
            className="w-32"
          />
          <span className="text-neutral-500">/</span>
          <Input
            id="short-link"
            type="text"
            placeholder="custom-key"
            value={data.key}
            onChange={(e) => setData('key', e.target.value)}
            error={errors.key}
            className="flex-1"
          />
        </div>
        {errors.key && (
          <p className="text-sm text-red-600">{errors.key}</p>
        )}
        <p className="text-xs text-neutral-500">
          Leave empty to generate a random short link
        </p>
      </div>

      {/* Advanced Options Toggle */}
      <div className="border-t border-neutral-200 pt-4">
        <button
          type="button"
          onClick={() => setShowAdvanced(!showAdvanced)}
          className="flex items-center gap-2 text-sm font-medium text-neutral-700 hover:text-neutral-900"
        >
          <Settings className="h-4 w-4" />
          Advanced Options
          <span className={cn(
            'transition-transform',
            showAdvanced ? 'rotate-180' : ''
          )}>
            ↓
          </span>
        </button>
      </div>

      {/* Advanced Options */}
      {showAdvanced && (
        <div className="space-y-4 border-t border-neutral-200 pt-4">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="link-title">Title (Optional)</Label>
              <Input
                id="link-title"
                type="text"
                placeholder="Custom title"
                value={data.title}
                onChange={(e) => setData('title', e.target.value)}
              />
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="link-description">Description (Optional)</Label>
              <Input
                id="link-description"
                type="text"
                placeholder="Custom description"
                value={data.description}
                onChange={(e) => setData('description', e.target.value)}
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="link-image">Custom Image (Optional)</Label>
            <Input
              id="link-image"
              type="url"
              placeholder="https://example.com/image.png"
              value={data.image}
              onChange={(e) => setData('image', e.target.value)}
            />
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="expiry-date">Expiry Date (Optional)</Label>
              <Input
                id="expiry-date"
                type="datetime-local"
                value={data.expiresAt}
                onChange={(e) => setData('expiresAt', e.target.value)}
              />
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="link-password">Password (Optional)</Label>
              <Input
                id="link-password"
                type="password"
                placeholder="Protect with password"
                value={data.password}
                onChange={(e) => setData('password', e.target.value)}
              />
            </div>
          </div>
        </div>
      )}

      {/* Form Actions */}
      <div className="flex justify-end space-x-3 border-t border-neutral-200 pt-6">
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
          disabled={!data.url}
        >
          {initialData ? 'Update Link' : 'Create Link'}
        </Button>
      </div>
    </form>
  );
}

export function LinkBuilderModal() {
  const { showLinkBuilder, setShowLinkBuilder } = useLinkBuilder();

  const handleSuccess = (data: any) => {
    setShowLinkBuilder(false);
    // Could trigger a refresh of links list here
  };

  const handleClose = () => {
    setShowLinkBuilder(false);
    
    // Clean up URL parameters if they exist
    const url = new URL(window.location.href);
    if (url.searchParams.has('newLink')) {
      url.searchParams.delete('newLink');
      url.searchParams.delete('newLinkDomain');
      window.history.replaceState({}, '', url.toString());
    }
  };

  return (
    <Modal
      showModal={showLinkBuilder}
      setShowModal={setShowLinkBuilder}
      onClose={handleClose}
      className="max-w-2xl"
    >
      <Dialog
        title="Create a new link"
        description="Add a new short link to your workspace"
      >
        <LinkBuilderForm onSuccess={handleSuccess} />
      </Dialog>
    </Modal>
  );
}
