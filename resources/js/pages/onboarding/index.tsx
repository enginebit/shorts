/**
 * Onboarding Index Page
 *
 * Dub.co Reference: /apps/web/app/onboarding/page.tsx
 *
 * Key Patterns Adopted:
 * - First workspace creation flow
 * - Pending invitation acceptance
 * - Clean onboarding UI with step-by-step guidance
 * - Workspace setup with validation
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia.js forms instead of Next.js form handling
 * - Integrates with our Laravel backend validation
 * - Uses our AuthLayout for consistent styling
 * - Maintains exact dub-main visual patterns
 */

import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button, Input } from '@/components/ui';
import AuthLayout from '@/layouts/auth-layout';
import { route } from 'ziggy-js';

interface PendingInvite {
  id: string;
  workspace: {
    name: string;
    slug: string;
    logo: string | null;
  };
  role: 'owner' | 'member';
  expiresAt: string;
}

interface OnboardingProps {
  pendingInvites: PendingInvite[];
  canCreateFreeWorkspace: boolean;
}

export default function OnboardingIndex({
  pendingInvites,
  canCreateFreeWorkspace
}: OnboardingProps) {
  const [showCreateForm, setShowCreateForm] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    slug: '',
  });

  const handleCreateWorkspace = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('onboarding.workspace'), {
      onSuccess: () => reset(),
    });
  };

  const handleAcceptInvite = (inviteId: string) => {
    post(route('onboarding.accept', inviteId));
  };

  const handleDeclineInvite = (inviteId: string) => {
    post(route('onboarding.decline', inviteId));
  };

  return (
    <AuthLayout>
      <Head title="Welcome to Shorts" />

      <div className="w-full max-w-md space-y-8">
        {/* Header */}
        <div className="text-center">
          <h1 className="text-2xl font-bold text-neutral-900">
            Welcome to Shorts
          </h1>
          <p className="mt-2 text-sm text-neutral-600">
            Get started by creating your first workspace or accepting an invitation.
          </p>
        </div>

        {/* Pending Invitations */}
        {pendingInvites.length > 0 && (
          <div className="space-y-4">
            <h2 className="text-lg font-semibold text-neutral-900">
              Pending Invitations
            </h2>

            {pendingInvites.map((invite) => (
              <div
                key={invite.id}
                className="rounded-lg border border-neutral-200 bg-white p-4"
              >
                <div className="flex items-center gap-3">
                  {invite.workspace.logo ? (
                    <img
                      src={invite.workspace.logo}
                      alt={invite.workspace.name}
                      className="size-10 rounded-lg"
                    />
                  ) : (
                    <div className="flex size-10 items-center justify-center rounded-lg bg-neutral-900 text-sm font-medium text-white">
                      {invite.workspace.name.charAt(0).toUpperCase()}
                    </div>
                  )}

                  <div className="flex-1">
                    <h3 className="font-medium text-neutral-900">
                      {invite.workspace.name}
                    </h3>
                    <p className="text-sm text-neutral-600">
                      Invited as {invite.role}
                    </p>
                  </div>
                </div>

                <div className="mt-4 flex gap-2">
                  <Button
                    variant="primary"
                    size="sm"
                    text="Accept"
                    onClick={() => handleAcceptInvite(invite.id)}
                    loading={processing}
                  />
                  <Button
                    variant="secondary"
                    size="sm"
                    text="Decline"
                    onClick={() => handleDeclineInvite(invite.id)}
                    loading={processing}
                  />
                </div>
              </div>
            ))}

            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-neutral-200" />
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="bg-white px-2 text-neutral-500">or</span>
              </div>
            </div>
          </div>
        )}

        {/* Create Workspace */}
        {canCreateFreeWorkspace && (
          <div className="space-y-4">
            {!showCreateForm ? (
              <Button
                variant="primary"
                className="w-full"
                text="Create your first workspace"
                onClick={() => setShowCreateForm(true)}
              />
            ) : (
              <form onSubmit={handleCreateWorkspace} className="space-y-4">
                <div>
                  <label htmlFor="name" className="block text-sm font-medium text-neutral-700">
                    Workspace name
                  </label>
                  <Input
                    id="name"
                    type="text"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="My Workspace"
                    error={errors.name}
                    required
                  />
                </div>

                <div>
                  <label htmlFor="slug" className="block text-sm font-medium text-neutral-700">
                    Workspace URL
                  </label>
                  <div className="mt-1 flex rounded-md shadow-sm">
                    <span className="inline-flex items-center rounded-l-md border border-r-0 border-neutral-300 bg-neutral-50 px-3 text-sm text-neutral-500">
                      shorts.com/
                    </span>
                    <Input
                      id="slug"
                      type="text"
                      value={data.slug}
                      onChange={(e) => setData('slug', e.target.value)}
                      placeholder="my-workspace"
                      error={errors.slug}
                      className="rounded-l-none"
                      required
                    />
                  </div>
                </div>

                <div className="flex gap-2">
                  <Button
                    type="submit"
                    variant="primary"
                    text="Create workspace"
                    loading={processing}
                    className="flex-1"
                  />
                  <Button
                    type="button"
                    variant="secondary"
                    text="Cancel"
                    onClick={() => setShowCreateForm(false)}
                    className="flex-1"
                  />
                </div>
              </form>
            )}
          </div>
        )}

        {/* Skip Option */}
        <div className="text-center">
          <button
            type="button"
            className="text-sm text-neutral-500 hover:text-neutral-700"
            onClick={() => post(route('onboarding.skip'))}
          >
            Skip for now
          </button>
        </div>
      </div>
    </AuthLayout>
  );
}
