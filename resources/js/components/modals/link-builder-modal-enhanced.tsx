/**
 * Enhanced Link Builder Modal Component - Phase 3B Implementation
 *
 * Dub.co Reference: /apps/web/ui/modals/link-builder/index.tsx
 *
 * Key Patterns Adopted:
 * - Comprehensive link creation and editing interface
 * - URL validation and preview with metadata
 * - Domain selection with workspace domains
 * - Custom key generation and validation
 * - UTM parameters and advanced targeting
 * - Link preview with social media cards
 * - Advanced options (expiration, password, device targeting)
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia useForm for form handling
 * - Integrated with Laravel LinksController API
 * - Enhanced with UTM and advanced options modals
 * - Maintains exact visual consistency with dub-main
 * - Full API integration with proper error handling
 */

import { useState, useEffect } from 'react';
import { useForm, router } from '@inertiajs/react';
import { toast } from 'sonner';
import { 
  Link as LinkIcon, 
  Globe, 
  Settings, 
  BarChart3, 
  Zap, 
  Calendar, 
  Lock,
  Smartphone,
  ExternalLink
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Modal, Dialog, Button, Input, Label, Select } from '@/components/ui';
import { useLinkBuilder } from '@/contexts/modal-context';
import { useWorkspace } from '@/contexts/workspace-context';
import { UTMModal } from './link-builder/utm-modal';
import { AdvancedModal } from './link-builder/advanced-modal';
import { LinkPreview } from './link-builder/link-preview';

interface EnhancedLinkFormData {
  url: string;
  domain: string;
  key: string;
  title?: string;
  description?: string;
  image?: string;
  expires_at?: string;
  password?: string;
  ios?: string;
  android?: string;
  geo?: Record<string, string>;
  // UTM Parameters
  utm_source?: string;
  utm_medium?: string;
  utm_campaign?: string;
  utm_term?: string;
  utm_content?: string;
  // Advanced Options
  externalId?: string;
  tenantId?: string;
  trackConversion?: boolean;
  enableComments?: boolean;
}

function EnhancedLinkBuilderForm({
  onSuccess,
  initialData,
}: {
  onSuccess?: (data: any) => void;
  initialData?: Partial<EnhancedLinkFormData & { id: string }>;
}) {
  const { workspace } = useWorkspace();
  const [showUTMModal, setShowUTMModal] = useState(false);
  const [showAdvancedModal, setShowAdvancedModal] = useState(false);
  const [generatedKey, setGeneratedKey] = useState('');

  const { data, setData, post, put, processing, errors, reset } = useForm<EnhancedLinkFormData>({
    url: initialData?.url || '',
    domain: initialData?.domain || 'dub.sh',
    key: initialData?.key || '',
    title: initialData?.title || '',
    description: initialData?.description || '',
    image: initialData?.image || '',
    expires_at: initialData?.expires_at || '',
    password: initialData?.password || '',
    ios: initialData?.ios || '',
    android: initialData?.android || '',
    geo: initialData?.geo || {},
    utm_source: initialData?.utm_source || '',
    utm_medium: initialData?.utm_medium || '',
    utm_campaign: initialData?.utm_campaign || '',
    utm_term: initialData?.utm_term || '',
    utm_content: initialData?.utm_content || '',
    externalId: initialData?.externalId || '',
    tenantId: initialData?.tenantId || '',
    trackConversion: initialData?.trackConversion || false,
    enableComments: initialData?.enableComments || false,
  });

  // Auto-generate key from URL
  useEffect(() => {
    if (data.url && !data.key && !initialData?.id) {
      try {
        const url = new URL(data.url);
        const pathname = url.pathname.replace(/^\/+|\/+$/g, '');
        const segments = pathname.split('/').filter(Boolean);
        const lastSegment = segments[segments.length - 1] || url.hostname.replace(/^www\./, '');
        const cleanKey = lastSegment
          .replace(/[^a-zA-Z0-9-_]/g, '-')
          .replace(/-+/g, '-')
          .replace(/^-|-$/g, '')
          .toLowerCase()
          .substring(0, 20);
        
        if (cleanKey && cleanKey !== generatedKey) {
          setGeneratedKey(cleanKey);
          setData('key', cleanKey);
        }
      } catch {
        // Invalid URL, don't generate key
      }
    }
  }, [data.url, data.key, initialData?.id, generatedKey, setData]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const submitData = {
      ...data,
      workspace_id: workspace?.id,
    };

    if (initialData?.id) {
      put(route('api.links.update', initialData.id), {
        onSuccess: (response) => {
          toast.success('Link updated successfully');
          onSuccess?.(response);
          reset();
        },
        onError: (errors) => {
          console.error('Link update errors:', errors);
          toast.error('Failed to update link');
        },
      });
    } else {
      post(route('api.links.store'), {
        onSuccess: (response) => {
          toast.success('Link created successfully');
          onSuccess?.(response);
          reset();
        },
        onError: (errors) => {
          console.error('Link creation errors:', errors);
          toast.error('Failed to create link');
        },
      });
    }
  };

  const handleUTMSave = (utmParams: Partial<EnhancedLinkFormData>) => {
    setData(prev => ({
      ...prev,
      ...utmParams,
    }));
  };

  const handleAdvancedSave = (advancedOptions: any) => {
    setData(prev => ({
      ...prev,
      ...advancedOptions,
    }));
  };

  const shortLink = data.domain && data.key ? `https://${data.domain}/${data.key}` : '';
  const hasUTMParams = Boolean(data.utm_source || data.utm_medium || data.utm_campaign || data.utm_term || data.utm_content);
  const hasAdvancedOptions = Boolean(data.expires_at || data.password || data.ios || data.android || data.externalId || data.tenantId);

  return (
    <>
      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Left Column - Form */}
          <div className="space-y-6">
            {/* Destination URL */}
            <div className="space-y-2">
              <Label htmlFor="url" className="flex items-center gap-2">
                <Globe className="h-4 w-4" />
                Destination URL
              </Label>
              <Input
                id="url"
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
            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-2">
                <Label htmlFor="domain">Domain</Label>
                <Select
                  value={data.domain}
                  onValueChange={(value) => setData('domain', value)}
                >
                  <option value="dub.sh">dub.sh</option>
                  <option value="short.link">short.link</option>
                  {/* Add workspace-specific domains here */}
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="key" className="flex items-center gap-2">
                  <LinkIcon className="h-4 w-4" />
                  Custom Key
                </Label>
                <Input
                  id="key"
                  type="text"
                  placeholder="custom-key"
                  value={data.key}
                  onChange={(e) => setData('key', e.target.value)}
                  error={errors.key}
                />
                {errors.key && (
                  <p className="text-sm text-red-600">{errors.key}</p>
                )}
              </div>
            </div>

            {/* Title and Description */}
            <div className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="title">Title (Optional)</Label>
                <Input
                  id="title"
                  type="text"
                  placeholder="Link title"
                  value={data.title}
                  onChange={(e) => setData('title', e.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="description">Description (Optional)</Label>
                <Input
                  id="description"
                  type="text"
                  placeholder="Link description"
                  value={data.description}
                  onChange={(e) => setData('description', e.target.value)}
                />
              </div>
            </div>

            {/* Feature Buttons */}
            <div className="flex flex-wrap gap-2">
              <Button
                type="button"
                variant="secondary"
                size="sm"
                onClick={() => setShowUTMModal(true)}
                className={cn(hasUTMParams && 'bg-blue-50 border-blue-200 text-blue-700')}
              >
                <BarChart3 className="h-4 w-4 mr-2" />
                UTM Builder
                {hasUTMParams && <span className="ml-1 text-xs">•</span>}
              </Button>
              <Button
                type="button"
                variant="secondary"
                size="sm"
                onClick={() => setShowAdvancedModal(true)}
                className={cn(hasAdvancedOptions && 'bg-purple-50 border-purple-200 text-purple-700')}
              >
                <Settings className="h-4 w-4 mr-2" />
                Advanced
                {hasAdvancedOptions && <span className="ml-1 text-xs">•</span>}
              </Button>
            </div>
          </div>

          {/* Right Column - Preview */}
          <div className="space-y-6">
            <LinkPreview
              url={data.url}
              shortLink={shortLink}
              title={data.title}
              description={data.description}
              image={data.image}
            />
          </div>
        </div>

        {/* Submit Button */}
        <div className="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200">
          <Button
            type="submit"
            loading={processing}
            disabled={!data.url || processing}
          >
            {initialData?.id ? 'Update Link' : 'Create Link'}
          </Button>
        </div>
      </form>

      {/* UTM Modal */}
      <UTMModal
        showModal={showUTMModal}
        setShowModal={setShowUTMModal}
        currentUrl={data.url}
        currentUTM={{
          utm_source: data.utm_source,
          utm_medium: data.utm_medium,
          utm_campaign: data.utm_campaign,
          utm_term: data.utm_term,
          utm_content: data.utm_content,
        }}
        onSave={handleUTMSave}
      />

      {/* Advanced Modal */}
      <AdvancedModal
        showModal={showAdvancedModal}
        setShowModal={setShowAdvancedModal}
        currentOptions={{
          expiresAt: data.expires_at,
          password: data.password,
          ios: data.ios,
          android: data.android,
          geo: data.geo,
          externalId: data.externalId,
          tenantId: data.tenantId,
          trackConversion: data.trackConversion,
          enableComments: data.enableComments,
        }}
        onSave={handleAdvancedSave}
      />
    </>
  );
}

export function EnhancedLinkBuilderModal() {
  const { showLinkBuilder, setShowLinkBuilder, linkToEdit } = useLinkBuilder();

  const handleSuccess = () => {
    setShowLinkBuilder(false);
    // Refresh the page to show updated links
    router.reload({ only: ['links'] });
  };

  return (
    <Modal
      showModal={showLinkBuilder}
      setShowModal={setShowLinkBuilder}
      className="max-w-6xl"
    >
      <Dialog
        title={linkToEdit ? 'Edit Link' : 'Create New Link'}
        description={linkToEdit ? 'Update your short link settings' : 'Create a new short link with advanced options'}
      >
        <EnhancedLinkBuilderForm
          onSuccess={handleSuccess}
          initialData={linkToEdit}
        />
      </Dialog>
    </Modal>
  );
}
