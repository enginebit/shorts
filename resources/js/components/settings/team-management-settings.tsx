/**
 * Team Management Settings Component
 *
 * Dub.co Reference: /apps/web/ui/workspaces/invite-teammates-form.tsx
 *
 * Key Patterns Adopted:
 * - Team member invitation and management
 * - Role-based permissions (owner, admin, member)
 * - Pending invitations display and management
 * - Member list with role management
 * - Bulk invitation support
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia useForm for form handling
 * - Integrates with Laravel WorkspaceController API
 * - Uses our form components and validation
 * - Maintains exact visual consistency with dub-main
 */

import { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { Plus, Trash2, Mail, Users, Crown, Shield, User, MoreHorizontal } from 'lucide-react';
import { cn } from '@/lib/utils';
import { 
  Card, 
  Button, 
  Input, 
  Label, 
  Select,
  Badge,
  Avatar,
  DropdownMenu
} from '@/components/ui';
import { toast } from 'sonner';

interface TeamMember {
  id: string;
  name: string;
  email: string;
  avatar?: string;
  role: string;
  joinedAt: string;
}

interface PendingInvite {
  id: string;
  email: string;
  role: string;
  expiresAt: string;
  createdAt: string;
}

interface TeamManagementSettingsProps {
  workspace: {
    id: string;
    name: string;
    members: TeamMember[];
    invites: PendingInvite[];
    plan: string;
  };
  canManage: boolean;
}

const ROLES = [
  { value: 'owner', label: 'Owner', icon: Crown, description: 'Full access to workspace' },
  { value: 'admin', label: 'Admin', icon: Shield, description: 'Manage workspace and members' },
  { value: 'member', label: 'Member', icon: User, description: 'Create and manage links' },
];

export function TeamManagementSettings({ 
  workspace, 
  canManage 
}: TeamManagementSettingsProps) {
  const [inviteEmails, setInviteEmails] = useState([{ email: '', role: 'member' }]);

  const { data, setData, post, processing, errors, reset } = useForm({
    invites: [{ email: '', role: 'member' }],
  });

  const addInviteField = () => {
    setInviteEmails([...inviteEmails, { email: '', role: 'member' }]);
  };

  const removeInviteField = (index: number) => {
    if (inviteEmails.length > 1) {
      const newInvites = inviteEmails.filter((_, i) => i !== index);
      setInviteEmails(newInvites);
    }
  };

  const updateInviteField = (index: number, field: 'email' | 'role', value: string) => {
    const newInvites = [...inviteEmails];
    newInvites[index][field] = value;
    setInviteEmails(newInvites);
  };

  const handleSendInvites = (e: React.FormEvent) => {
    e.preventDefault();
    
    const validInvites = inviteEmails.filter(invite => invite.email.trim());
    
    if (validInvites.length === 0) {
      toast.error('Please enter at least one email address');
      return;
    }

    post(route('workspaces.invites.store', workspace.id), {
      data: { invites: validInvites },
      onSuccess: () => {
        toast.success(`${validInvites.length} invitation${validInvites.length > 1 ? 's' : ''} sent successfully`);
        setInviteEmails([{ email: '', role: 'member' }]);
        router.reload({ only: ['workspace'] });
      },
      onError: (errors) => {
        console.error('Invite errors:', errors);
        toast.error('Failed to send invitations');
      },
    });
  };

  const handleCancelInvite = (inviteId: string) => {
    router.delete(route('workspaces.invites.destroy', [workspace.id, inviteId]), {
      onSuccess: () => {
        toast.success('Invitation cancelled');
        router.reload({ only: ['workspace'] });
      },
      onError: () => {
        toast.error('Failed to cancel invitation');
      },
    });
  };

  const handleUpdateMemberRole = (memberId: string, newRole: string) => {
    router.patch(route('workspaces.members.update', [workspace.id, memberId]), {
      role: newRole,
    }, {
      onSuccess: () => {
        toast.success('Member role updated');
        router.reload({ only: ['workspace'] });
      },
      onError: () => {
        toast.error('Failed to update member role');
      },
    });
  };

  const handleRemoveMember = (memberId: string) => {
    router.delete(route('workspaces.members.destroy', [workspace.id, memberId]), {
      onSuccess: () => {
        toast.success('Member removed from workspace');
        router.reload({ only: ['workspace'] });
      },
      onError: () => {
        toast.error('Failed to remove member');
      },
    });
  };

  const getRoleIcon = (role: string) => {
    const roleConfig = ROLES.find(r => r.value === role);
    return roleConfig?.icon || User;
  };

  const getRoleBadgeColor = (role: string) => {
    switch (role) {
      case 'owner': return 'bg-purple-100 text-purple-800';
      case 'admin': return 'bg-blue-100 text-blue-800';
      default: return 'bg-neutral-100 text-neutral-800';
    }
  };

  return (
    <div className="space-y-6">
      {/* Invite Team Members */}
      {canManage && (
        <Card>
          <div className="p-6">
            <div className="space-y-4">
              <div>
                <h3 className="text-lg font-medium text-neutral-900">Invite Team Members</h3>
                <p className="text-sm text-neutral-500">
                  Invite teammates to collaborate on your workspace.
                </p>
              </div>

              <form onSubmit={handleSendInvites} className="space-y-4">
                {inviteEmails.map((invite, index) => (
                  <div key={index} className="flex items-center gap-3">
                    <div className="flex-1">
                      <Input
                        type="email"
                        placeholder="teammate@example.com"
                        value={invite.email}
                        onChange={(e) => updateInviteField(index, 'email', e.target.value)}
                        required
                      />
                    </div>
                    <div className="w-32">
                      <Select
                        value={invite.role}
                        onValueChange={(value) => updateInviteField(index, 'role', value)}
                      >
                        {ROLES.filter(role => role.value !== 'owner').map((role) => (
                          <option key={role.value} value={role.value}>
                            {role.label}
                          </option>
                        ))}
                      </Select>
                    </div>
                    {inviteEmails.length > 1 && (
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => removeInviteField(index)}
                        className="text-red-600 hover:text-red-700"
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    )}
                  </div>
                ))}

                <div className="flex items-center justify-between">
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={addInviteField}
                    disabled={inviteEmails.length >= 10}
                  >
                    <Plus className="h-4 w-4 mr-2" />
                    Add Another
                  </Button>

                  <Button
                    type="submit"
                    loading={processing}
                    disabled={processing}
                  >
                    <Mail className="h-4 w-4 mr-2" />
                    Send Invitations
                  </Button>
                </div>
              </form>
            </div>
          </div>
        </Card>
      )}

      {/* Pending Invitations */}
      {workspace.invites.length > 0 && (
        <Card>
          <div className="p-6">
            <div className="space-y-4">
              <div>
                <h3 className="text-lg font-medium text-neutral-900">Pending Invitations</h3>
                <p className="text-sm text-neutral-500">
                  Invitations that haven't been accepted yet.
                </p>
              </div>

              <div className="space-y-3">
                {workspace.invites.map((invite) => (
                  <div key={invite.id} className="flex items-center justify-between p-3 border border-neutral-200 rounded-lg">
                    <div className="flex items-center gap-3">
                      <div className="h-8 w-8 rounded-full bg-neutral-100 flex items-center justify-center">
                        <Mail className="h-4 w-4 text-neutral-600" />
                      </div>
                      <div>
                        <p className="text-sm font-medium text-neutral-900">{invite.email}</p>
                        <p className="text-xs text-neutral-500">
                          Invited {new Date(invite.createdAt).toLocaleDateString()}
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3">
                      <Badge className={getRoleBadgeColor(invite.role)}>
                        {invite.role}
                      </Badge>
                      {canManage && (
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleCancelInvite(invite.id)}
                          className="text-red-600 hover:text-red-700"
                        >
                          Cancel
                        </Button>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </Card>
      )}

      {/* Team Members */}
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div>
              <h3 className="text-lg font-medium text-neutral-900">Team Members</h3>
              <p className="text-sm text-neutral-500">
                Manage your workspace team members and their roles.
              </p>
            </div>

            <div className="space-y-3">
              {workspace.members.map((member) => {
                const RoleIcon = getRoleIcon(member.role);
                
                return (
                  <div key={member.id} className="flex items-center justify-between p-3 border border-neutral-200 rounded-lg">
                    <div className="flex items-center gap-3">
                      <Avatar
                        src={member.avatar}
                        alt={member.name}
                        fallback={member.name.charAt(0).toUpperCase()}
                        className="h-8 w-8"
                      />
                      <div>
                        <p className="text-sm font-medium text-neutral-900">{member.name}</p>
                        <p className="text-xs text-neutral-500">{member.email}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-3">
                      <Badge className={getRoleBadgeColor(member.role)}>
                        <RoleIcon className="h-3 w-3 mr-1" />
                        {member.role}
                      </Badge>
                      {canManage && member.role !== 'owner' && (
                        <DropdownMenu>
                          <DropdownMenu.Trigger asChild>
                            <Button variant="ghost" size="sm">
                              <MoreHorizontal className="h-4 w-4" />
                            </Button>
                          </DropdownMenu.Trigger>
                          <DropdownMenu.Content align="end">
                            <DropdownMenu.Item
                              onClick={() => handleUpdateMemberRole(member.id, 'admin')}
                              disabled={member.role === 'admin'}
                            >
                              Make Admin
                            </DropdownMenu.Item>
                            <DropdownMenu.Item
                              onClick={() => handleUpdateMemberRole(member.id, 'member')}
                              disabled={member.role === 'member'}
                            >
                              Make Member
                            </DropdownMenu.Item>
                            <DropdownMenu.Separator />
                            <DropdownMenu.Item
                              onClick={() => handleRemoveMember(member.id)}
                              className="text-red-600"
                            >
                              Remove from workspace
                            </DropdownMenu.Item>
                          </DropdownMenu.Content>
                        </DropdownMenu>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      </Card>
    </div>
  );
}
