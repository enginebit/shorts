/**
 * Account Profile Settings Component
 *
 * Dub.co Reference: /apps/web/ui/account/ (profile management patterns)
 *
 * Key Patterns Adopted:
 * - Personal profile management
 * - Avatar upload with validation
 * - Name and email management
 * - Email verification status
 * - Real-time form updates
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia useForm for form handling
 * - Integrates with Laravel UserController API
 * - Uses our form components and validation
 * - Maintains exact visual consistency with dub-main
 */

import { useState, useRef } from 'react';
import { useForm } from '@inertiajs/react';
import { Upload, User, Mail, CheckCircle, AlertCircle } from 'lucide-react';
import { cn } from '@/lib/utils';
import { 
  Card, 
  Button, 
  Input, 
  Label,
  Badge,
  Avatar
} from '@/components/ui';
import { toast } from 'sonner';

interface AccountProfileSettingsProps {
  user: {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    emailVerifiedAt?: string;
  };
}

export function AccountProfileSettings({ user }: AccountProfileSettingsProps) {
  const [avatarPreview, setAvatarPreview] = useState<string | null>(user.avatar || null);
  const [avatarFile, setAvatarFile] = useState<File | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const { data, setData, patch, processing, errors } = useForm({
    name: user.name,
    email: user.email,
    avatar: user.avatar || '',
  });

  const handleAvatarChange = (event: React.ChangeEvent<HTMLInputElement>) => {
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

    setAvatarFile(file);

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
      const result = e.target?.result as string;
      setAvatarPreview(result);
    };
    reader.readAsDataURL(file);
  };

  const handleProfileSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const formData = new FormData();
    formData.append('name', data.name);
    formData.append('email', data.email);
    
    if (avatarFile) {
      formData.append('avatar', avatarFile);
    }

    patch(route('account.update'), {
      data: formData,
      forceFormData: true,
      onSuccess: () => {
        toast.success('Profile updated successfully');
        setAvatarFile(null);
      },
      onError: (errors) => {
        console.error('Profile update errors:', errors);
        toast.error('Failed to update profile');
      },
    });
  };

  const handleResendVerification = () => {
    patch(route('verification.send'), {
      onSuccess: () => {
        toast.success('Verification email sent');
      },
      onError: () => {
        toast.error('Failed to send verification email');
      },
    });
  };

  const hasChanges = 
    data.name !== user.name ||
    data.email !== user.email ||
    avatarFile !== null;

  return (
    <div className="space-y-6">
      {/* Profile Picture */}
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div>
              <h3 className="text-lg font-medium text-neutral-900">Profile Picture</h3>
              <p className="text-sm text-neutral-500">
                Upload a profile picture to personalize your account.
              </p>
            </div>
            
            <div className="flex items-center gap-6">
              {/* Avatar Preview */}
              <Avatar
                src={avatarPreview}
                alt={user.name}
                fallback={user.name.charAt(0).toUpperCase()}
                className="h-20 w-20"
              />

              {/* Upload Button */}
              <div className="space-y-2">
                <input
                  ref={fileInputRef}
                  type="file"
                  accept="image/*"
                  onChange={handleAvatarChange}
                  className="hidden"
                />
                <Button
                  type="button"
                  variant="secondary"
                  onClick={() => fileInputRef.current?.click()}
                >
                  <Upload className="h-4 w-4 mr-2" />
                  Upload Picture
                </Button>
                <p className="text-xs text-neutral-500">
                  Square image recommended. PNG, JPG up to 2MB.
                </p>
              </div>
            </div>
          </div>
        </div>
      </Card>

      {/* Personal Information */}
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div>
              <h3 className="text-lg font-medium text-neutral-900">Personal Information</h3>
              <p className="text-sm text-neutral-500">
                Update your personal details and contact information.
              </p>
            </div>
            
            <form onSubmit={handleProfileSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="name">Full Name</Label>
                <Input
                  id="name"
                  type="text"
                  placeholder="Your full name"
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
                <Label htmlFor="email">Email Address</Label>
                <div className="space-y-2">
                  <Input
                    id="email"
                    type="email"
                    placeholder="your@email.com"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    error={errors.email}
                    required
                  />
                  {errors.email && (
                    <p className="text-sm text-red-600">{errors.email}</p>
                  )}
                  
                  {/* Email Verification Status */}
                  <div className="flex items-center gap-2">
                    {user.emailVerifiedAt ? (
                      <Badge className="bg-green-100 text-green-800">
                        <CheckCircle className="h-3 w-3 mr-1" />
                        Verified
                      </Badge>
                    ) : (
                      <div className="flex items-center gap-2">
                        <Badge className="bg-amber-100 text-amber-800">
                          <AlertCircle className="h-3 w-3 mr-1" />
                          Unverified
                        </Badge>
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          onClick={handleResendVerification}
                          className="text-blue-600 hover:text-blue-700"
                        >
                          Resend verification
                        </Button>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              <div className="flex items-center justify-end pt-4">
                <Button
                  type="submit"
                  loading={processing}
                  disabled={!hasChanges || processing}
                >
                  Save Changes
                </Button>
              </div>
            </form>
          </div>
        </div>
      </Card>

      {/* Account Information */}
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div>
              <h3 className="text-lg font-medium text-neutral-900">Account Information</h3>
              <p className="text-sm text-neutral-500">
                View your account details and status.
              </p>
            </div>
            
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label className="text-sm font-medium text-neutral-700">User ID</Label>
                <div className="p-3 bg-neutral-50 rounded-lg border">
                  <code className="text-sm text-neutral-900">{user.id}</code>
                </div>
              </div>
              
              <div className="space-y-2">
                <Label className="text-sm font-medium text-neutral-700">Account Status</Label>
                <div className="p-3 bg-neutral-50 rounded-lg border">
                  <Badge className="bg-green-100 text-green-800">
                    <CheckCircle className="h-3 w-3 mr-1" />
                    Active
                  </Badge>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Card>
    </div>
  );
}
