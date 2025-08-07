/**
 * Account Danger Zone Component
 */

import { useState } from 'react';
import { router } from '@inertiajs/react';
import { AlertTriangle, Trash2, Download } from 'lucide-react';
import { Card, Button, Input, Label, Modal, Dialog } from '@/components/ui';
import { toast } from 'sonner';

interface AccountDangerZoneProps {
  user: {
    id: string;
    email: string;
  };
}

export function AccountDangerZone({ user }: AccountDangerZoneProps) {
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const [confirmationText, setConfirmationText] = useState('');
  const [isDeleting, setIsDeleting] = useState(false);

  const handleDeleteAccount = async () => {
    if (confirmationText !== user.email) {
      toast.error('Please type your email address exactly to confirm deletion');
      return;
    }

    setIsDeleting(true);

    router.delete(route('account.destroy'), {
      onSuccess: () => {
        toast.success('Account deleted successfully');
        router.visit('/');
      },
      onError: () => {
        toast.error('Failed to delete account');
        setIsDeleting(false);
      },
    });
  };

  const handleExportData = () => {
    router.get(route('account.export'), {}, {
      onSuccess: () => {
        toast.success('Data export started. You will receive an email when it\'s ready.');
      },
      onError: () => {
        toast.error('Failed to start data export');
      },
    });
  };

  return (
    <div className="space-y-6">
      {/* Export Data */}
      <Card className="border-blue-200 bg-blue-50">
        <div className="p-6">
          <div className="flex items-start justify-between">
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <Download className="h-5 w-5 text-blue-600" />
                <h3 className="text-lg font-medium text-blue-900">Export Account Data</h3>
              </div>
              <p className="text-sm text-blue-800">
                Download all your account data including profile information, links, and analytics.
                This is recommended before deleting your account.
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

      {/* Delete Account */}
      <Card className="border-red-200 bg-red-50">
        <div className="p-6">
          <div className="flex items-start justify-between">
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <AlertTriangle className="h-5 w-5 text-red-600" />
                <h3 className="text-lg font-medium text-red-900">Delete Account</h3>
              </div>
              <p className="text-sm text-red-800">
                Permanently delete your account and all associated data. This action cannot be undone.
                All your links will stop working immediately.
              </p>
            </div>
            <Button
              variant="destructive"
              onClick={() => setShowDeleteModal(true)}
            >
              <Trash2 className="h-4 w-4 mr-2" />
              Delete Account
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
          title="Delete Account"
          description="This action cannot be undone. All data will be permanently deleted."
        >
          <div className="space-y-4">
            <div className="rounded-lg bg-red-50 border border-red-200 p-4">
              <div className="flex items-center gap-2 mb-2">
                <AlertTriangle className="h-4 w-4 text-red-600" />
                <p className="text-sm font-medium text-red-800">This will permanently:</p>
              </div>
              <ul className="text-sm text-red-700 space-y-1 ml-6">
                <li>• Delete your profile and account data</li>
                <li>• Remove you from all workspaces</li>
                <li>• Delete all your personal links</li>
                <li>• Cancel any active subscriptions</li>
              </ul>
            </div>

            <div className="space-y-2">
              <Label htmlFor="confirm-delete">
                Type <strong>{user.email}</strong> to confirm deletion:
              </Label>
              <Input
                id="confirm-delete"
                type="text"
                placeholder={user.email}
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
                onClick={handleDeleteAccount}
                loading={isDeleting}
                disabled={confirmationText !== user.email || isDeleting}
              >
                Delete Account
              </Button>
            </div>
          </div>
        </Dialog>
      </Modal>
    </div>
  );
}
