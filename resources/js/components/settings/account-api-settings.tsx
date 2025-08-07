/**
 * Account API Settings Component
 */

import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Key, Copy, Trash2, Plus, Eye, EyeOff } from 'lucide-react';
import { Card, Button, Input, Label, Modal, Dialog } from '@/components/ui';
import { toast } from 'sonner';

interface APIKey {
  id: string;
  name: string;
  lastUsed?: string;
  createdAt: string;
}

interface AccountAPISettingsProps {
  user: {
    apiKeys: APIKey[];
  };
}

export function AccountAPISettings({ user }: AccountAPISettingsProps) {
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [newKeyName, setNewKeyName] = useState('');
  const [newApiKey, setNewApiKey] = useState<string | null>(null);
  const [showKey, setShowKey] = useState(false);
  const [isCreating, setIsCreating] = useState(false);

  const handleCreateKey = async () => {
    if (!newKeyName.trim()) {
      toast.error('Please enter a name for the API key');
      return;
    }

    setIsCreating(true);

    router.post(route('account.api-keys.store'), {
      name: newKeyName,
    }, {
      onSuccess: (response: any) => {
        setNewApiKey(response.props.apiKey);
        setNewKeyName('');
        toast.success('API key created successfully');
        router.reload({ only: ['user'] });
      },
      onError: () => {
        toast.error('Failed to create API key');
      },
      onFinish: () => {
        setIsCreating(false);
      },
    });
  };

  const handleDeleteKey = (keyId: string) => {
    router.delete(route('account.api-keys.destroy', keyId), {
      onSuccess: () => {
        toast.success('API key deleted');
        router.reload({ only: ['user'] });
      },
      onError: () => {
        toast.error('Failed to delete API key');
      },
    });
  };

  const handleCopyKey = async (key: string) => {
    try {
      await navigator.clipboard.writeText(key);
      toast.success('API key copied to clipboard');
    } catch {
      toast.error('Failed to copy API key');
    }
  };

  return (
    <div className="space-y-6">
      {/* API Keys */}
      <Card>
        <div className="p-6">
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg font-medium text-neutral-900">API Keys</h3>
                <p className="text-sm text-neutral-500">
                  Manage your API keys for programmatic access.
                </p>
              </div>
              <Button onClick={() => setShowCreateModal(true)}>
                <Plus className="h-4 w-4 mr-2" />
                Create API Key
              </Button>
            </div>
            
            {user.apiKeys.length > 0 ? (
              <div className="space-y-3">
                {user.apiKeys.map((apiKey) => (
                  <div key={apiKey.id} className="flex items-center justify-between p-3 border border-neutral-200 rounded-lg">
                    <div className="flex items-center gap-3">
                      <div className="h-8 w-8 rounded-full bg-neutral-100 flex items-center justify-center">
                        <Key className="h-4 w-4 text-neutral-600" />
                      </div>
                      <div>
                        <p className="text-sm font-medium text-neutral-900">{apiKey.name}</p>
                        <p className="text-xs text-neutral-500">
                          Created {new Date(apiKey.createdAt).toLocaleDateString()}
                          {apiKey.lastUsed && ` • Last used ${new Date(apiKey.lastUsed).toLocaleDateString()}`}
                        </p>
                      </div>
                    </div>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleDeleteKey(apiKey.id)}
                      className="text-red-600 hover:text-red-700"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8">
                <Key className="mx-auto h-8 w-8 text-neutral-400 mb-2" />
                <p className="text-sm text-neutral-500">No API keys created yet</p>
              </div>
            )}
          </div>
        </div>
      </Card>

      {/* Create API Key Modal */}
      <Modal
        showModal={showCreateModal}
        setShowModal={setShowCreateModal}
        className="max-w-md"
      >
        <Dialog
          title="Create API Key"
          description="Generate a new API key for programmatic access"
        >
          <div className="space-y-4">
            {newApiKey ? (
              <div className="space-y-4">
                <div className="rounded-lg bg-green-50 border border-green-200 p-4">
                  <p className="text-sm text-green-800 mb-2">
                    API key created successfully! Copy it now as you won't be able to see it again.
                  </p>
                </div>
                
                <div className="space-y-2">
                  <Label>Your API Key</Label>
                  <div className="flex items-center gap-2">
                    <Input
                      type={showKey ? 'text' : 'password'}
                      value={newApiKey}
                      readOnly
                      className="font-mono text-sm"
                    />
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => setShowKey(!showKey)}
                    >
                      {showKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => handleCopyKey(newApiKey)}
                    >
                      <Copy className="h-4 w-4" />
                    </Button>
                  </div>
                </div>

                <div className="flex items-center justify-end pt-4">
                  <Button
                    onClick={() => {
                      setShowCreateModal(false);
                      setNewApiKey(null);
                      setShowKey(false);
                    }}
                  >
                    Done
                  </Button>
                </div>
              </div>
            ) : (
              <div className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="key-name">API Key Name</Label>
                  <Input
                    id="key-name"
                    type="text"
                    placeholder="My API Key"
                    value={newKeyName}
                    onChange={(e) => setNewKeyName(e.target.value)}
                  />
                </div>

                <div className="flex items-center justify-end gap-3 pt-4">
                  <Button
                    variant="secondary"
                    onClick={() => {
                      setShowCreateModal(false);
                      setNewKeyName('');
                    }}
                  >
                    Cancel
                  </Button>
                  <Button
                    onClick={handleCreateKey}
                    loading={isCreating}
                    disabled={!newKeyName.trim() || isCreating}
                  >
                    Create API Key
                  </Button>
                </div>
              </div>
            )}
          </div>
        </Dialog>
      </Modal>
    </div>
  );
}
