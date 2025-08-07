/**
 * Account Notification Settings Component
 */

import { useForm } from '@inertiajs/react';
import { Bell, Mail, Shield, Megaphone } from 'lucide-react';
import { Card, Button, Label, Switch } from '@/components/ui';
import { toast } from 'sonner';

interface AccountNotificationSettingsProps {
  user: {
    notificationPreferences: {
      emailNotifications: boolean;
      marketingEmails: boolean;
      securityAlerts: boolean;
    };
  };
}

export function AccountNotificationSettings({ user }: AccountNotificationSettingsProps) {
  const { data, setData, patch, processing } = useForm({
    emailNotifications: user.notificationPreferences.emailNotifications,
    marketingEmails: user.notificationPreferences.marketingEmails,
    securityAlerts: user.notificationPreferences.securityAlerts,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    patch(route('account.notifications.update'), {
      onSuccess: () => {
        toast.success('Notification preferences updated');
      },
      onError: () => {
        toast.error('Failed to update preferences');
      },
    });
  };

  return (
    <div className="space-y-6">
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div>
              <h3 className="text-lg font-medium text-neutral-900">Email Notifications</h3>
              <p className="text-sm text-neutral-500">
                Choose which emails you'd like to receive.
              </p>
            </div>
            
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Mail className="h-5 w-5 text-neutral-600" />
                    <div>
                      <Label className="text-sm font-medium">Email Notifications</Label>
                      <p className="text-xs text-neutral-500">
                        Receive notifications about your account activity
                      </p>
                    </div>
                  </div>
                  <Switch
                    checked={data.emailNotifications}
                    onCheckedChange={(checked) => setData('emailNotifications', checked)}
                  />
                </div>

                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Shield className="h-5 w-5 text-neutral-600" />
                    <div>
                      <Label className="text-sm font-medium">Security Alerts</Label>
                      <p className="text-xs text-neutral-500">
                        Important security notifications (always enabled)
                      </p>
                    </div>
                  </div>
                  <Switch
                    checked={data.securityAlerts}
                    onCheckedChange={(checked) => setData('securityAlerts', checked)}
                    disabled
                  />
                </div>

                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <Megaphone className="h-5 w-5 text-neutral-600" />
                    <div>
                      <Label className="text-sm font-medium">Marketing Emails</Label>
                      <p className="text-xs text-neutral-500">
                        Product updates, tips, and promotional content
                      </p>
                    </div>
                  </div>
                  <Switch
                    checked={data.marketingEmails}
                    onCheckedChange={(checked) => setData('marketingEmails', checked)}
                  />
                </div>
              </div>

              <div className="flex items-center justify-end pt-4 border-t border-neutral-200">
                <Button
                  type="submit"
                  loading={processing}
                  disabled={processing}
                >
                  Save Preferences
                </Button>
              </div>
            </form>
          </div>
        </div>
      </Card>
    </div>
  );
}
