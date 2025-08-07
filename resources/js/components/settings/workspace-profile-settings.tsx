/**
 * Workspace Profile Settings Component
 *
 * Dub.co Reference: /apps/web/ui/workspaces/upload-logo.tsx and page-client.tsx
 *
 * Key Patterns Adopted:
 * - Form-based workspace profile management
 * - Logo upload with file validation
 * - Real-time form updates with API integration
 * - Permission-based form disabling
 * - Visual feedback for form states
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia useForm for form handling
 * - Integrates with Laravel WorkspaceController API
 * - Uses our form components and validation
 * - Maintains exact visual consistency with dub-main
 */

import { useState, useRef } from 'react';
import { useForm } from '@inertiajs/react';
import { Upload, Building, Info } from 'lucide-react';
import { cn } from '@/lib/utils';
import { 
  Card, 
  Button, 
  Input, 
  Label, 
  Textarea,
  Separator 
} from '@/components/ui';
import { toast } from 'sonner';

interface WorkspaceProfileSettingsProps {
  workspace: {
    id: string;
    name: string;
    slug: string;
    logo?: string;
    description?: string;
  };
  canManage: boolean;
}

export function WorkspaceProfileSettings({ 
  workspace, 
  canManage 
}: WorkspaceProfileSettingsProps) {
  const [logoPreview, setLogoPreview] = useState<string | null>(workspace.logo || null);
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const { data, setData, patch, processing, errors, reset } = useForm({
    name: workspace.name,
    slug: workspace.slug,
    description: workspace.description || '',
    logo: workspace.logo || '',
  });

  const handleLogoChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    // Validate file type
    if (!file.type.startsWith('image/')) {
      toast.error('Please select an image file');
      return;
    }

    // Validate file size (2MB max)
    if (file.size > 2 * 1024 * 1024) {
      toast.error('Image must be less than 2MB');
      return;
    }

    setLogoFile(file);

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      const result = e.target?.result as string;
      setLogoPreview(result);
    };
    reader.readAsDataURL(file);
  };

  const handleProfileSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const formData = new FormData();
    formData.append('name', data.name);
    formData.append('slug', data.slug);
    formData.append('description', data.description);
    
    if (logoFile) {
      formData.append('logo', logoFile);
    }

    // Use patch method for form data
    patch(route('workspaces.update', workspace.id), {
      data: formData,
      forceFormData: true,
      onSuccess: () => {
        toast.success('Workspace profile updated successfully');
        setLogoFile(null);
      },
      onError: (errors) => {
        console.error('Profile update errors:', errors);
        toast.error('Failed to update workspace profile');
      },
    });
  };

  const hasChanges = 
    data.name !== workspace.name ||
    data.slug !== workspace.slug ||
    data.description !== (workspace.description || '') ||
    logoFile !== null;

  return (
    <div className="space-y-6">
      {/* Workspace Name */}
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div>
              <h3 className="text-lg font-medium text-neutral-900">Workspace Name</h3>
              <p className="text-sm text-neutral-500">
                This is the name of your workspace that will be displayed throughout the application.
              </p>
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="name">Name</Label>
              <Input
                id="name"
                type="text"
                placeholder="My Workspace"
                value={data.name}
                onChange={(e) => setData('name', e.target.value)}
                disabled={!canManage}
                error={errors.name}
                maxLength={32}
              />
              {errors.name && (
                <p className="text-sm text-red-600">{errors.name}</p>
              )}
              <p className="text-xs text-neutral-500">Max 32 characters.</p>
            </div>
          </div>
        </div>
      </Card>

      {/* Workspace Slug */}
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div>
              <h3 className="text-lg font-medium text-neutral-900">Workspace Slug</h3>
              <p className="text-sm text-neutral-500">
                This is your workspace's unique identifier in URLs.
              </p>
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="slug">Slug</Label>
              <div className="flex items-center">
                <span className="text-sm text-neutral-500 mr-2">
                  {window.location.origin}/
                </span>
                <Input
                  id="slug"
                  type="text"
                  placeholder="my-workspace"
                  value={data.slug}
                  onChange={(e) => setData('slug', e.target.value)}
                  disabled={!canManage}
                  error={errors.slug}
                  pattern="^[a-z0-9-]+$"
                  maxLength={48}
                  className="flex-1"
                />
              </div>
              {errors.slug && (
                <p className="text-sm text-red-600">{errors.slug}</p>
              )}
              <p className="text-xs text-neutral-500">
                Only lowercase letters, numbers, and dashes. Max 48 characters.
              </p>
            </div>
          </div>
        </div>
      </Card>

      {/* Workspace Description */}
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div>
              <h3 className="text-lg font-medium text-neutral-900">Description</h3>
              <p className="text-sm text-neutral-500">
                A brief description of your workspace (optional).
              </p>
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="description">Description</Label>
              <Textarea
                id="description"
                placeholder="Describe your workspace..."
                value={data.description}
                onChange={(e) => setData('description', e.target.value)}
                disabled={!canManage}
                rows={3}
                maxLength={160}
              />
              <p className="text-xs text-neutral-500">Max 160 characters.</p>
            </div>
          </div>
        </div>
      </Card>

      {/* Workspace Logo */}
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div>
              <h3 className="text-lg font-medium text-neutral-900">Workspace Logo</h3>
              <p className="text-sm text-neutral-500">
                Upload a logo for your workspace. This will be displayed in the sidebar and other locations.
              </p>
            </div>
            
            <div className="flex items-center gap-6">
              {/* Logo Preview */}
              <div className="relative">
                <div className="h-20 w-20 rounded-full border-2 border-neutral-200 bg-neutral-50 flex items-center justify-center overflow-hidden">
                  {logoPreview ? (
                    <img
                      src={logoPreview}
                      alt="Workspace logo"
                      className="h-full w-full object-cover"
                    />
                  ) : (
                    <Building className="h-8 w-8 text-neutral-400" />
                  )}
                </div>
              </div>

              {/* Upload Button */}
              <div className="space-y-2">
                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/*"
                  onChange={handleLogoChange}
                  className="hidden"
                  disabled={!canManage}
                />
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => fileInputRef.current?.click()}
                  disabled={!canManage}
                >
                  <Upload className="h-4 w-4 mr-2" />
                  Upload Logo
                </Button>
                <p className="text-xs text-neutral-500">
                  Square image recommended. PNG, JPG up to 2MB.
                </p>
              </div>
            </div>
          </div>
        </div>
      </Card>

      {/* Save Button */}
      {canManage && (
        <div className="flex items-center justify-end">
          <Button
            onClick={handleProfileSubmit}
            loading={processing}
            disabled={!hasChanges || processing}
          >
            Save Changes
          </Button>
        </div>
      )}

      {!canManage && (
        <div className="rounded-lg bg-amber-50 border border-amber-200 p-4">
          <div className="flex items-center gap-2">
            <Info className="h-4 w-4 text-amber-600" />
            <p className="text-sm text-amber-800">
              You don't have permission to modify workspace settings. Contact a workspace owner or admin.
            </p>
          </div>
        </div>
      )}
    </div>
  );
}
