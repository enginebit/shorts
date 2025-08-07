/**
 * Workspace Settings Page
 *
 * Dub.co Reference: /apps/web/app/app.dub.co/(dashboard)/[slug]/settings/(basic-layout)/page-client.tsx
 *
 * Key Patterns Adopted:
 * - Comprehensive workspace management interface
 * - Form-based settings with real-time updates
 * - Team member management and invitations
 * - Workspace profile settings (name, logo, description)
 * - Permission-based access control
 * - Workspace deletion and transfer options
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia page props for workspace data
 * - Integrates with Laravel WorkspaceController API
 * - Uses our form components and validation
 * - Maintains exact visual consistency with dub-main
 */

import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Settings, Users, Trash2, Upload, Building } from 'lucide-react';
import { AppLayout } from '@/layouts/app-layout';
import { 
  PageWidthWrapper, 
  PageHeader,
  Button,
  Input,
  Label,
  Card,
  Separator
} from '@/components/ui';
import { useWorkspace } from '@/contexts/workspace-context';
import { WorkspaceProfileSettings } from '@/components/settings/workspace-profile-settings';
import { TeamManagementSettings } from '@/components/settings/team-management-settings';
import { WorkspaceDangerZone } from '@/components/settings/workspace-danger-zone';
import { toast } from 'sonner';

interface WorkspaceSettingsProps {
  workspace: {
    id: string;
    name: string;
    slug: string;
    logo?: string;
    description?: string;
    plan: string;
    role: string;
    invites: Array<{
      id: string;
      email: string;
      role: string;
      expiresAt: string;
      createdAt: string;
    }>;
    members: Array<{
      id: string;
      name: string;
      email: string;
      avatar?: string;
      role: string;
      joinedAt: string;
    }>;
  };
}

export default function WorkspaceSettings({ workspace }: WorkspaceSettingsProps) {
  const [activeSection, setActiveSection] = useState('profile');

  const sections = [
    {
      id: 'profile',
      name: 'Profile',
      icon: Building,
      description: 'Manage your workspace profile and branding',
    },
    {
      id: 'team',
      name: 'Team',
      icon: Users,
      description: 'Invite and manage team members',
    },
    {
      id: 'danger',
      name: 'Danger Zone',
      icon: Trash2,
      description: 'Delete or transfer workspace',
    },
  ];

  const canManageWorkspace = workspace.role === 'owner' || workspace.role === 'admin';

  return (
    <AppLayout>
      <Head title={`Settings - ${workspace.name}`} />

      <PageHeader
        title="Workspace Settings"
        description="Manage your workspace profile, team members, and preferences"
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
                <WorkspaceProfileSettings
                  workspace={workspace}
                  canManage={canManageWorkspace}
                />
              )}

              {activeSection === 'team' && (
                <TeamManagementSettings
                  workspace={workspace}
                  canManage={canManageWorkspace}
                />
              )}

              {activeSection === 'danger' && (
                <WorkspaceDangerZone
                  workspace={workspace}
                  canManage={workspace.role === 'owner'}
                />
              )}
            </div>
          </div>
        </div>
      </PageWidthWrapper>
    </AppLayout>
  );
}
