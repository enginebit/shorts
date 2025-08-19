/**
 * Onboarding Workspace Creation Page
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/app/app.dub.co/(onboarding)/onboarding/(steps)/workspace/page.tsx
 *
 * Key Patterns Adopted:
 * - StepPage layout with title and description
 * - CreateWorkspaceForm component integration
 * - Proper onboarding flow progression
 * - Help link for workspace explanation
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia.js navigation instead of Next.js router
 * - Integrates with our Laravel workspace creation system
 * - Maintains exact visual consistency with dub-main
 */

import { Head, useForm } from '@inertiajs/react';
import { Button, Input, PageWidthWrapper } from '@/components/ui';
import { route } from 'ziggy-js';
import { useState } from 'react';

export default function Workspace() {
  const [showForm, setShowForm] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    slug: '',
  });

  const skipForm = useForm({});

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('onboarding.workspace'), {
      onSuccess: () => {
        reset();
        // Redirect will be handled by the backend
      },
    });
  };

  return (
    <>
      <Head title="Create your workspace - Shorts" />

      <PageWidthWrapper>
        <div className="mx-auto max-w-md flex min-h-screen flex-col items-center justify-center px-4">
        <div className="w-full max-w-md space-y-8">
          {/* Header */}
          <div className="text-center">
            <h1 className="text-2xl font-bold text-neutral-900">
              Create your workspace
            </h1>
            <p className="mt-2 text-sm text-neutral-600">
              Set up a shared space to manage your links with your team.{' '}
              <a
                href="https://dub.co/help/article/what-is-a-workspace"
                target="_blank"
                rel="noopener noreferrer"
                className="cursor-help font-medium underline decoration-dotted underline-offset-2 transition-colors hover:text-neutral-700"
              >
                Learn more.
              </a>
            </p>
          </div>

          {/* Workspace Creation Form */}
          <div className="space-y-6">
            <form onSubmit={handleSubmit} className="space-y-4">
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
                  autoFocus
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
                {errors.slug && (
                  <p className="mt-1 text-sm text-red-600">{errors.slug}</p>
                )}
              </div>

              <div className="flex gap-3 pt-4">
                <Button
                  type="submit"
                  text={processing ? 'Creating workspace...' : 'Create workspace'}
                  loading={processing}
                  disabled={processing}
                  className="flex-1"
                />
                <Button
                  type="button"
                  variant="secondary"
                  text="Skip for now"
                  onClick={() => {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    console.log('Skip button clicked:', {
                      csrfTokenExists: !!csrfToken,
                      csrfTokenPrefix: csrfToken?.substring(0, 10) + '...',
                      formData: skipForm.data,
                      routeUrl: route('onboarding.skip'),
                    });

                    skipForm.post(route('onboarding.skip'), {
                      onSuccess: () => {
                        console.log('Skip successful');
                      },
                      onError: (errors) => {
                        console.error('Skip failed with errors:', errors);
                        // Show user-friendly error message
                        if (errors.message) {
                          alert(`Error: ${errors.message}`);
                        } else {
                          alert('Failed to skip workspace creation. Please try again.');
                        }
                      },
                    });
                  }}
                  disabled={skipForm.processing}
                  loading={skipForm.processing}
                  className="flex-1"
                />
              </div>
            </form>
          </div>

          {/* Progress indicator */}
          <div className="flex justify-center">
            <div className="flex space-x-2">
              <div className="h-2 w-2 rounded-full bg-neutral-900"></div>
              <div className="h-2 w-2 rounded-full bg-neutral-300"></div>
              <div className="h-2 w-2 rounded-full bg-neutral-300"></div>
              <div className="h-2 w-2 rounded-full bg-neutral-300"></div>
            </div>
          </div>
        </div>
          </div>
      </PageWidthWrapper>
    </>
  );
}
