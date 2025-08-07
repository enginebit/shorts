/**
 * Account Settings Page
 *
 * Dub.co Reference: /apps/web/app/account/settings/page.tsx
 *
 * Key Patterns Adopted:
 * - Personal account management interface
 * - Profile settings with avatar upload
 * - Security settings and password management
 * - API key management and generation
 * - Notification preferences
 * - Account deletion with confirmation
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia page props for user data
 * - Integrates with Laravel UserController API
 * - Uses our form components and validation
 * - Maintains exact visual consistency with dub-main
 */

import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { User, Shield, Key, Bell, Trash2 } from 'lucide-react';
import { AppLayout } from '@/layouts/app-layout';
import { 
  PageWidthWrapper, 
  PageHeader
} from '@/components/ui';
import { AccountProfileSettings } from '@/components/settings/account-profile-settings';
import { AccountSecuritySettings } from '@/components/settings/account-security-settings';
import { AccountAPISettings } from '@/components/settings/account-api-settings';
import { AccountNotificationSettings } from '@/components/settings/account-notification-settings';
import { AccountDangerZone } from '@/components/settings/account-danger-zone';

interface AccountSettingsProps {
  user: {
    id: string;
    name: string;
    email: string;
    avatar?: string;
    emailVerifiedAt?: string;
    twoFactorEnabled: boolean;
    apiKeys: Array<{
      id: string;
      name: string;
      lastUsed?: string;
      createdAt: string;
    }>;
    notificationPreferences: {
      emailNotifications: boolean;
      marketingEmails: boolean;
      securityAlerts: boolean;
    };
  };
}

export default function AccountSettings({ user }: AccountSettingsProps) {
  const [activeSection, setActiveSection] = useState('profile');

  const sections = [
    {
      id: 'profile',
      name: 'Profile',
      icon: User,
      description: 'Manage your personal information',
    },
    {
      id: 'security',
      name: 'Security',
      icon: Shield,
      description: 'Password and security settings',
    },
    {
      id: 'api',
      name: 'API Keys',
      icon: Key,
      description: 'Manage your API access',
    },
    {
      id: 'notifications',
      name: 'Notifications',
      icon: Bell,
      description: 'Email and notification preferences',
    },
    {
      id: 'danger',
      name: 'Danger Zone',
      icon: Trash2,
      description: 'Delete your account',
    },
  ];

  return (
    <AppLayout>
      <Head title="Account Settings" />

      <PageHeader
        title="Account Settings"
        description="Manage your personal account settings and preferences"
      />

      <PageWidthWrapper className="py-8">
        <div className="grid grid-cols-1 gap-8 lg:grid-cols-4">
          {/* Settings Navigation */}
          <div className="lg:col-span-1">
            <nav className="space-y-1">
              {sections.map((section) => {
                const Icon = section.icon;
                const isActive = activeSection === section.id;
                
                return (
                  <button
                    key={section.id}
                    onClick={() => setActiveSection(section.id)}
                    className={`w-full flex items-center gap-3 px-3 py-2 text-left text-sm font-medium rounded-lg transition-colors ${
                      isActive
                        ? 'bg-neutral-100 text-neutral-900'
                        : 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-900'
                    }`}
                  >
                    <Icon className="h-4 w-4" />
                    <div>
                      <div>{section.name}</div>
                      <div className="text-xs text-neutral-500 font-normal">
                        {section.description}
                      </div>
                    </div>
                  </button>
                );
              })}
            </nav>
          </div>

          {/* Settings Content */}
          <div className="lg:col-span-3">
            <div className="space-y-8">
              {activeSection === 'profile' && (
                <AccountProfileSettings user={user} />
              )}

              {activeSection === 'security' && (
                <AccountSecuritySettings user={user} />
              )}

              {activeSection === 'api' && (
                <AccountAPISettings user={user} />
              )}

              {activeSection === 'notifications' && (
                <AccountNotificationSettings user={user} />
              )}

              {activeSection === 'danger' && (
                <AccountDangerZone user={user} />
              )}
            </div>
          </div>
        </div>
      </PageWidthWrapper>
    </AppLayout>
  );
}
