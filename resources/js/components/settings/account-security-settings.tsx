/**
 * Account Security Settings Component
 */

import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Shield, Key, Smartphone, AlertTriangle } from 'lucide-react';
import { Card, Button, Input, Label, Badge } from '@/components/ui';
import { toast } from 'sonner';

interface AccountSecuritySettingsProps {
  user: {
    id: string;
    email: string;
    twoFactorEnabled: boolean;
  };
}

export function AccountSecuritySettings({ user }: AccountSecuritySettingsProps) {
  const { data, setData, patch, processing, errors } = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    patch(route('account.password.update'), {
      onSuccess: () => {
        toast.success('Password updated successfully');
        setData({
          current_password: '',
          password: '',
          password_confirmation: '',
        });
      },
      onError: () => {
        toast.error('Failed to update password');
      },
    });
  };

  const handleToggle2FA = () => {
    patch(route('account.2fa.toggle'), {
      onSuccess: () => {
        toast.success(user.twoFactorEnabled ? '2FA disabled' : '2FA enabled');
      },
      onError: () => {
        toast.error('Failed to update 2FA settings');
      },
    });
  };

  return (
    <div className="space-y-6">
      {/* Change Password */}
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div>
              <h3 className="text-lg font-medium text-neutral-900">Change Password</h3>
              <p className="text-sm text-neutral-500">
                Update your password to keep your account secure.
              </p>
            </div>
            
            <form onSubmit={handlePasswordSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="current_password">Current Password</Label>
                <Input
                  id="current_password"
                  type="password"
                  value={data.current_password}
                  onChange={(e) => setData('current_password', e.target.value)}
                  error={errors.current_password}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password">New Password</Label>
                <Input
                  id="password"
                  type="password"
                  value={data.password}
                  onChange={(e) => setData('password', e.target.value)}
                  error={errors.password}
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="password_confirmation">Confirm New Password</Label>
                <Input
                  id="password_confirmation"
                  type="password"
                  value={data.password_confirmation}
                  onChange={(e) => setData('password_confirmation', e.target.value)}
                  error={errors.password_confirmation}
                  required
                />
              </div>

              <div className="flex items-center justify-end pt-4">
                <Button
                  type="submit"
                  loading={processing}
                  disabled={processing}
                >
                  Update Password
                </Button>
              </div>
            </form>
          </div>
        </div>
      </Card>

      {/* Two-Factor Authentication */}
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg font-medium text-neutral-900">Two-Factor Authentication</h3>
                <p className="text-sm text-neutral-500">
                  Add an extra layer of security to your account.
                </p>
              </div>
              <Badge className={user.twoFactorEnabled ? 'bg-green-100 text-green-800' : 'bg-neutral-100 text-neutral-800'}>
                <Smartphone className="h-3 w-3 mr-1" />
                {user.twoFactorEnabled ? 'Enabled' : 'Disabled'}
              </Badge>
            </div>
            
            <div className="flex items-center justify-between pt-4 border-t border-neutral-200">
              <div className="space-y-1">
                <p className="text-sm font-medium text-neutral-900">
                  {user.twoFactorEnabled ? 'Disable 2FA' : 'Enable 2FA'}
                </p>
                <p className="text-xs text-neutral-500">
                  {user.twoFactorEnabled 
                    ? 'Remove two-factor authentication from your account'
                    : 'Secure your account with two-factor authentication'
                  }
                </p>
              </div>
              <Button
                variant={user.twoFactorEnabled ? 'destructive' : 'default'}
                onClick={handleToggle2FA}
              >
                {user.twoFactorEnabled ? 'Disable' : 'Enable'} 2FA
              </Button>
            </div>
          </div>
        </div>
      </Card>
    </div>
  );
}
