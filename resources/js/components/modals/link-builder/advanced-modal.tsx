/**
 * Advanced Options Modal Component
 *
 * Dub.co Reference: /apps/web/ui/modals/link-builder/advanced-modal.tsx
 *
 * Key Patterns Adopted:
 * - Advanced link configuration options
 * - External ID and tenant ID management
 * - Link expiration and password protection
 * - Device-specific targeting (iOS/Android)
 * - Geographic targeting options
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses our Modal and Dialog components
 * - Simplified for initial implementation
 * - Maintains exact visual consistency
 * - Integrated with our form handling system
 */

import { useState } from 'react';
import { Calendar, Lock, Smartphone, Globe, Settings } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Modal, Dialog, Button, Input, Label, Checkbox } from '@/components/ui';

interface AdvancedOptions {
  expiresAt?: string;
  password?: string;
  ios?: string;
  android?: string;
  geo?: Record<string, string>;
  externalId?: string;
  tenantId?: string;
  trackConversion?: boolean;
  enableComments?: boolean;
}

interface AdvancedModalProps {
  showModal: boolean;
  setShowModal: (show: boolean) => void;
  currentOptions: AdvancedOptions;
  onSave: (options: AdvancedOptions) => void;
}

export function AdvancedModal({
  showModal,
  setShowModal,
  currentOptions,
  onSave,
}: AdvancedModalProps) {
  const [options, setOptions] = useState<AdvancedOptions>(currentOptions);

  const handleSave = () => {
    onSave(options);
    setShowModal(false);
  };

  const handleOptionChange = <K extends keyof AdvancedOptions>(
    key: K,
    value: AdvancedOptions[K]
  ) => {
    setOptions(prev => ({
      ...prev,
      [key]: value,
    }));
  };

  return (
    <Modal
      showModal={showModal}
      setShowModal={setShowModal}
      className="max-w-2xl"
    >
      <Dialog
        title="Advanced Options"
        description="Configure advanced settings for your short link"
      >
        <div className="space-y-6">
          {/* Link Expiration */}
          <div className="space-y-3">
            <div className="flex items-center gap-2">
              <Calendar className="h-4 w-4 text-neutral-600" />
              <Label className="text-sm font-medium text-neutral-900">
                Link Expiration
              </Label>
            </div>
            <div className="space-y-2">
              <Input
                type="datetime-local"
                value={options.expiresAt || ''}
                onChange={(e) => handleOptionChange('expiresAt', e.target.value)}
                placeholder="Set expiration date and time"
              />
              <p className="text-xs text-neutral-500">
                Link will automatically expire and redirect to a 404 page after this date
              </p>
            </div>
          </div>

          {/* Password Protection */}
          <div className="space-y-3">
            <div className="flex items-center gap-2">
              <Lock className="h-4 w-4 text-neutral-600" />
              <Label className="text-sm font-medium text-neutral-900">
                Password Protection
              </Label>
            </div>
            <div className="space-y-2">
              <Input
                type="password"
                value={options.password || ''}
                onChange={(e) => handleOptionChange('password', e.target.value)}
                placeholder="Enter password to protect this link"
              />
              <p className="text-xs text-neutral-500">
                Users will need to enter this password before accessing the link
              </p>
            </div>
          </div>

          {/* Device Targeting */}
          <div className="space-y-3">
            <div className="flex items-center gap-2">
              <Smartphone className="h-4 w-4 text-neutral-600" />
              <Label className="text-sm font-medium text-neutral-900">
                Device-Specific Targeting
              </Label>
            </div>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="ios-url" className="text-sm">
                  iOS URL
                </Label>
                <Input
                  id="ios-url"
                  type="url"
                  value={options.ios || ''}
                  onChange={(e) => handleOptionChange('ios', e.target.value)}
                  placeholder="https://apps.apple.com/..."
                />
                <p className="text-xs text-neutral-500">
                  Redirect iOS users to this URL
                </p>
              </div>
              <div className="space-y-2">
                <Label htmlFor="android-url" className="text-sm">
                  Android URL
                </Label>
                <Input
                  id="android-url"
                  type="url"
                  value={options.android || ''}
                  onChange={(e) => handleOptionChange('android', e.target.value)}
                  placeholder="https://play.google.com/..."
                />
                <p className="text-xs text-neutral-500">
                  Redirect Android users to this URL
                </p>
              </div>
            </div>
          </div>

          {/* External Integration */}
          <div className="space-y-3">
            <div className="flex items-center gap-2">
              <Settings className="h-4 w-4 text-neutral-600" />
              <Label className="text-sm font-medium text-neutral-900">
                External Integration
              </Label>
            </div>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="external-id" className="text-sm">
                  External ID
                </Label>
                <Input
                  id="external-id"
                  type="text"
                  value={options.externalId || ''}
                  onChange={(e) => handleOptionChange('externalId', e.target.value)}
                  placeholder="custom-id-123"
                />
                <p className="text-xs text-neutral-500">
                  Custom identifier for external systems
                </p>
              </div>
              <div className="space-y-2">
                <Label htmlFor="tenant-id" className="text-sm">
                  Tenant ID
                </Label>
                <Input
                  id="tenant-id"
                  type="text"
                  value={options.tenantId || ''}
                  onChange={(e) => handleOptionChange('tenantId', e.target.value)}
                  placeholder="tenant-123"
                />
                <p className="text-xs text-neutral-500">
                  Multi-tenant organization identifier
                </p>
              </div>
            </div>
          </div>

          {/* Additional Options */}
          <div className="space-y-3">
            <Label className="text-sm font-medium text-neutral-900">
              Additional Options
            </Label>
            <div className="space-y-3">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="track-conversion"
                  checked={options.trackConversion || false}
                  onCheckedChange={(checked) => 
                    handleOptionChange('trackConversion', checked as boolean)
                  }
                />
                <Label htmlFor="track-conversion" className="text-sm">
                  Enable conversion tracking
                </Label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="enable-comments"
                  checked={options.enableComments || false}
                  onCheckedChange={(checked) => 
                    handleOptionChange('enableComments', checked as boolean)
                  }
                />
                <Label htmlFor="enable-comments" className="text-sm">
                  Enable link comments
                </Label>
              </div>
            </div>
          </div>

          {/* Actions */}
          <div className="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200">
            <Button
              variant="secondary"
              onClick={() => setShowModal(false)}
            >
              Cancel
            </Button>
            <Button onClick={handleSave}>
              Save Advanced Options
            </Button>
          </div>
        </div>
      </Dialog>
    </Modal>
  );
}
