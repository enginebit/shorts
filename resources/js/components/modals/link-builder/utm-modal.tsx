/**
 * UTM Parameters Modal Component
 *
 * Dub.co Reference: /apps/web/ui/modals/link-builder/utm-modal.tsx
 *
 * Key Patterns Adopted:
 * - UTM parameter builder with templates
 * - Real-time URL construction with UTM parameters
 * - Form validation and parameter management
 * - Template system for common UTM combinations
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses our Modal and Dialog components
 * - Simplified template system for initial implementation
 * - Maintains exact visual consistency
 * - Integrated with our form handling system
 */

import { useState, useEffect } from 'react';
import { ExternalLink, Info } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Modal, Dialog, Button, Input, Label, Select } from '@/components/ui';

interface UTMParameters {
  utm_source: string;
  utm_medium: string;
  utm_campaign: string;
  utm_term: string;
  utm_content: string;
}

interface UTMModalProps {
  showModal: boolean;
  setShowModal: (show: boolean) => void;
  currentUrl: string;
  currentUTM: Partial<UTMParameters>;
  onSave: (utm: Partial<UTMParameters>) => void;
}

const UTM_TEMPLATES = [
  {
    name: 'Email Campaign',
    params: {
      utm_source: 'email',
      utm_medium: 'email',
      utm_campaign: 'newsletter',
    },
  },
  {
    name: 'Social Media',
    params: {
      utm_source: 'social',
      utm_medium: 'social',
      utm_campaign: 'organic',
    },
  },
  {
    name: 'Google Ads',
    params: {
      utm_source: 'google',
      utm_medium: 'cpc',
      utm_campaign: 'search',
    },
  },
  {
    name: 'Facebook Ads',
    params: {
      utm_source: 'facebook',
      utm_medium: 'cpc',
      utm_campaign: 'social',
    },
  },
];

const UTM_FIELDS = [
  {
    key: 'utm_source' as keyof UTMParameters,
    label: 'Source',
    placeholder: 'google, facebook, newsletter',
    description: 'The referrer (e.g. google, newsletter)',
  },
  {
    key: 'utm_medium' as keyof UTMParameters,
    label: 'Medium',
    placeholder: 'cpc, email, social',
    description: 'Marketing medium (e.g. cpc, banner, email)',
  },
  {
    key: 'utm_campaign' as keyof UTMParameters,
    label: 'Campaign',
    placeholder: 'summer_sale, product_launch',
    description: 'Campaign name (e.g. summer_sale)',
  },
  {
    key: 'utm_term' as keyof UTMParameters,
    label: 'Term',
    placeholder: 'running shoes, fitness',
    description: 'Paid keywords (for paid search)',
  },
  {
    key: 'utm_content' as keyof UTMParameters,
    label: 'Content',
    placeholder: 'logolink, textlink',
    description: 'Ad content (to differentiate similar ads)',
  },
];

export function UTMModal({
  showModal,
  setShowModal,
  currentUrl,
  currentUTM,
  onSave,
}: UTMModalProps) {
  const [utmParams, setUTMParams] = useState<Partial<UTMParameters>>(currentUTM);
  const [previewUrl, setPreviewUrl] = useState('');

  // Update preview URL when parameters change
  useEffect(() => {
    if (!currentUrl) {
      setPreviewUrl('');
      return;
    }

    try {
      const url = new URL(currentUrl);
      
      // Add UTM parameters
      Object.entries(utmParams).forEach(([key, value]) => {
        if (value && value.trim()) {
          url.searchParams.set(key, value.trim());
        } else {
          url.searchParams.delete(key);
        }
      });

      setPreviewUrl(url.toString());
    } catch {
      setPreviewUrl(currentUrl);
    }
  }, [currentUrl, utmParams]);

  const handleTemplateSelect = (template: typeof UTM_TEMPLATES[0]) => {
    setUTMParams(prev => ({
      ...prev,
      ...template.params,
    }));
  };

  const handleParameterChange = (key: keyof UTMParameters, value: string) => {
    setUTMParams(prev => ({
      ...prev,
      [key]: value,
    }));
  };

  const handleSave = () => {
    onSave(utmParams);
    setShowModal(false);
  };

  const handleClear = () => {
    setUTMParams({});
  };

  const hasParameters = Object.values(utmParams).some(value => value && value.trim());

  return (
    <Modal
      showModal={showModal}
      setShowModal={setShowModal}
      className="max-w-2xl"
    >
      <Dialog
        title="UTM Parameters"
        description="Add UTM parameters to track the performance of your marketing campaigns"
      >
        <div className="space-y-6">
          {/* Templates */}
          <div>
            <Label className="text-sm font-medium text-neutral-900 mb-3 block">
              Quick Templates
            </Label>
            <div className="grid grid-cols-2 gap-2">
              {UTM_TEMPLATES.map((template) => (
                <button
                  key={template.name}
                  onClick={() => handleTemplateSelect(template)}
                  className="text-left p-3 rounded-lg border border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50 transition-colors"
                >
                  <div className="font-medium text-sm text-neutral-900">
                    {template.name}
                  </div>
                  <div className="text-xs text-neutral-500 mt-1">
                    {Object.entries(template.params).map(([key, value]) => (
                      <span key={key} className="mr-2">
                        {key.replace('utm_', '')}: {value}
                      </span>
                    ))}
                  </div>
                </button>
              ))}
            </div>
          </div>

          {/* UTM Parameters */}
          <div className="space-y-4">
            <Label className="text-sm font-medium text-neutral-900">
              UTM Parameters
            </Label>
            {UTM_FIELDS.map((field) => (
              <div key={field.key} className="space-y-2">
                <div className="flex items-center gap-2">
                  <Label htmlFor={field.key} className="text-sm font-medium">
                    {field.label}
                  </Label>
                  <div className="group relative">
                    <Info className="h-3 w-3 text-neutral-400 cursor-help" />
                    <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block">
                      <div className="bg-neutral-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                        {field.description}
                      </div>
                    </div>
                  </div>
                </div>
                <Input
                  id={field.key}
                  type="text"
                  placeholder={field.placeholder}
                  value={utmParams[field.key] || ''}
                  onChange={(e) => handleParameterChange(field.key, e.target.value)}
                />
              </div>
            ))}
          </div>

          {/* Preview URL */}
          {previewUrl && (
            <div className="space-y-2">
              <Label className="text-sm font-medium text-neutral-900">
                Preview URL
              </Label>
              <div className="p-3 bg-neutral-50 rounded-lg border">
                <div className="flex items-start gap-2">
                  <ExternalLink className="h-4 w-4 text-neutral-400 mt-0.5 flex-shrink-0" />
                  <div className="min-w-0 flex-1">
                    <p className="text-sm text-neutral-700 break-all">
                      {previewUrl}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* Actions */}
          <div className="flex items-center justify-between pt-4 border-t border-neutral-200">
            <Button
              variant="secondary"
              onClick={handleClear}
              disabled={!hasParameters}
            >
              Clear All
            </Button>
            <div className="flex items-center gap-3">
              <Button
                variant="secondary"
                onClick={() => setShowModal(false)}
              >
                Cancel
              </Button>
              <Button onClick={handleSave}>
                Save UTM Parameters
              </Button>
            </div>
          </div>
        </div>
      </Dialog>
    </Modal>
  );
}
