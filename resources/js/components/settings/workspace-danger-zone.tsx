/**
 * Workspace Danger Zone Component
 *
 * Dub.co Reference: /apps/web/ui/workspaces/delete-workspace.tsx
 *
 * Key Patterns Adopted:
 * - Dangerous workspace operations (delete, transfer)
 * - Confirmation dialogs with workspace name verification
 * - Clear warning messages and consequences
 * - Owner-only permissions for critical operations
 * - Data export options before deletion
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia router for navigation
 * - Integrates with Laravel WorkspaceController API
 * - Uses our modal and confirmation components
 * - Maintains exact visual consistency with dub-main
 */

import { useState } from 'react';
import { router } from '@inertiajs/react';
import { AlertTriangle, Trash2, ArrowRightLeft, Download, Info } from 'lucide-react';
import { cn } from '@/lib/utils';
import { 
  Card, 
  Button, 
  Input, 
  Label,
  Modal,
  Dialog
} from '@/components/ui';
import { toast } from 'sonner';

interface WorkspaceDangerZoneProps {
  workspace: {
    id: string;
    name: string;
    slug: string;
    plan: string;
  };
  canManage: boolean;
}

export function WorkspaceDangerZone({ 
  workspace, 
  canManage 
}: WorkspaceDangerZoneProps) {
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [showTransferModal, setShowTransferModal] = useState(false);
  const [confirmationText, setConfirmationText] = useState('');
  const [transferEmail, setTransferEmail] = useState('');
  const [isDeleting, setIsDeleting] = useState(false);
  const [isTransferring, setIsTransferring] = useState(false);

  const handleDeleteWorkspace = async () => {
    if (confirmationText !== workspace.name) {
      toast.error('Please type the workspace name exactly to confirm deletion');
      return;
    }

    setIsDeleting(true);

    router.delete(route('workspaces.destroy', workspace.id), {
      onSuccess: () => {
        toast.success('Workspace deleted successfully');
        router.visit('/dashboard');
      },
      onError: (errors) => {
        console.error('Delete workspace errors:', errors);
        toast.error('Failed to delete workspace');
        setIsDeleting(false);
      },
    });
  };

  const handleTransferWorkspace = async () => {
    if (!transferEmail.trim()) {
      toast.error('Please enter an email address');
      return;
    }

    if (confirmationText !== workspace.name) {
      toast.error('Please type the workspace name exactly to confirm transfer');
      return;
    }

    setIsTransferring(true);

    router.post(route('workspaces.transfer', workspace.id), {
      email: transferEmail,
    }, {
      onSuccess: () => {
        toast.success('Workspace transfer initiated. The new owner will receive an email to accept the transfer.');
        setShowTransferModal(false);
        setTransferEmail('');
        setConfirmationText('');
      },
      onError: (errors) => {
        console.error('Transfer workspace errors:', errors);
        toast.error('Failed to initiate workspace transfer');
      },
      onFinish: () => {
        setIsTransferring(false);
      },
    });
  };

  const handleExportData = () => {
    router.get(route('workspaces.export', workspace.id), {}, {
      onSuccess: () => {
        toast.success('Data export started. You will receive an email when it\'s ready.');
      },
      onError: () => {
        toast.error('Failed to start data export');
      },
    });
  };

  if (!canManage) {
    return (
      <Card className="border-amber-200 bg-amber-50">
        <div className="p-6">
          <div className="flex items-center gap-3">
            <Info className="h-5 w-5 text-amber-600" />
            <div>
              <h3 className="text-lg font-medium text-amber-900">Access Restricted</h3>
              <p className="text-sm text-amber-800">
                Only workspace owners can access dangerous operations like deletion and transfer.
              </p>
            </div>
          </div>
        </div>
      </Card>
    );
  }

  return (
    <div className="space-y-6">
      {/* Export Data */}
      <Card className="border-blue-200 bg-blue-50">
        <div className="p-6">
          <div className="flex items-start justify-between">
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <Download className="h-5 w-5 text-blue-600" />
                <h3 className="text-lg font-medium text-blue-900">Export Workspace Data</h3>
              </div>
              <p className="text-sm text-blue-800">
                Download all your workspace data including links, analytics, and settings.
                This is recommended before making any destructive changes.
              </p>
            </div>
            <Button
              variant="secondary"
              onClick={handleExportData}
              className="bg-blue-100 border-blue-300 text-blue-900 hover:bg-blue-200"
            >
              <Download className="h-4 w-4 mr-2" />
              Export Data
            </Button>
          </div>
        </div>
      </Card>

      {/* Transfer Workspace */}
      <Card className="border-orange-200 bg-orange-50">
        <div className="p-6">
          <div className="flex items-start justify-between">
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <ArrowRightLeft className="h-5 w-5 text-orange-600" />
                <h3 className="text-lg font-medium text-orange-900">Transfer Workspace</h3>
              </div>
              <p className="text-sm text-orange-800">
                Transfer ownership of this workspace to another user. You will lose all access
                to this workspace once the transfer is completed.
              </p>
            </div>
            <Button
              variant="secondary"
              onClick={() => setShowTransferModal(true)}
              className="bg-orange-100 border-orange-300 text-orange-900 hover:bg-orange-200"
            >
              <ArrowRightLeft className="h-4 w-4 mr-2" />
              Transfer Workspace
            </Button>
          </div>
        </div>
      </Card>

      {/* Delete Workspace */}
      <Card className="border-red-200 bg-red-50">
        <div className="p-6">
          <div className="flex items-start justify-between">
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <AlertTriangle className="h-5 w-5 text-red-600" />
                <h3 className="text-lg font-medium text-red-900">Delete Workspace</h3>
              </div>
              <p className="text-sm text-red-800">
                Permanently delete this workspace and all of its data. This action cannot be undone.
                All links will stop working immediately.
              </p>
            </div>
            <Button
              variant="destructive"
              onClick={() => setShowDeleteModal(true)}
            >
              <Trash2 className="h-4 w-4 mr-2" />
              Delete Workspace
            </Button>
          </div>
        </div>
      </Card>

      {/* Delete Confirmation Modal */}
      <Modal
        showModal={showDeleteModal}
        setShowModal={setShowDeleteModal}
        className="max-w-md"
      >
        <Dialog
          title="Delete Workspace"
          description="This action cannot be undone. All data will be permanently deleted."
        >
          <div className="space-y-4">
            <div className="rounded-lg bg-red-50 border border-red-200 p-4">
              <div className="flex items-center gap-2 mb-2">
                <AlertTriangle className="h-4 w-4 text-red-600" />
                <p className="text-sm font-medium text-red-800">This will permanently:</p>
              </div>
              <ul className="text-sm text-red-700 space-y-1 ml-6">
                <li>• Delete all links and make them inaccessible</li>
                <li>• Remove all analytics data</li>
                <li>• Cancel your subscription</li>
                <li>• Remove all team members</li>
              </ul>
            </div>

            <div className="space-y-2">
              <Label htmlFor="confirm-delete">
                Type <strong>{workspace.name}</strong> to confirm deletion:
              </Label>
              <Input
                id="confirm-delete"
                type="text"
                placeholder={workspace.name}
                value={confirmationText}
                onChange={(e) => setConfirmationText(e.target.value)}
              />
            </div>

            <div className="flex items-center justify-end gap-3 pt-4">
              <Button
                variant="secondary"
                onClick={() => {
                  setShowDeleteModal(false);
                  setConfirmationText('');
                }}
              >
                Cancel
              </Button>
              <Button
                variant="destructive"
                onClick={handleDeleteWorkspace}
                loading={isDeleting}
                disabled={confirmationText !== workspace.name || isDeleting}
              >
                Delete Workspace
              </Button>
            </div>
          </div>
        </Dialog>
      </Modal>

      {/* Transfer Confirmation Modal */}
      <Modal
        showModal={showTransferModal}
        setShowModal={setShowTransferModal}
        className="max-w-md"
      >
        <Dialog
          title="Transfer Workspace"
          description="Transfer ownership to another user"
        >
          <div className="space-y-4">
            <div className="rounded-lg bg-orange-50 border border-orange-200 p-4">
              <p className="text-sm text-orange-800">
                The new owner will receive an email to accept the transfer. You will lose all
                access to this workspace once the transfer is completed.
              </p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="transfer-email">New owner's email address:</Label>
              <Input
                id="transfer-email"
                type="email"
                placeholder="newowner@example.com"
                value={transferEmail}
                onChange={(e) => setTransferEmail(e.target.value)}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="confirm-transfer">
                Type <strong>{workspace.name}</strong> to confirm transfer:
              </Label>
              <Input
                id="confirm-transfer"
                type="text"
                placeholder={workspace.name}
                value={confirmationText}
                onChange={(e) => setConfirmationText(e.target.value)}
              />
            </div>

            <div className="flex items-center justify-end gap-3 pt-4">
              <Button
                variant="secondary"
                onClick={() => {
                  setShowTransferModal(false);
                  setTransferEmail('');
                  setConfirmationText('');
                }}
              >
                Cancel
              </Button>
              <Button
                onClick={handleTransferWorkspace}
                loading={isTransferring}
                disabled={!transferEmail.trim() || confirmationText !== workspace.name || isTransferring}
                className="bg-orange-600 hover:bg-orange-700"
              >
                Transfer Workspace
              </Button>
            </div>
          </div>
        </Dialog>
      </Modal>
    </div>
  );
}
