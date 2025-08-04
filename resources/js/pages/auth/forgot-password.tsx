/**
 * Forgot Password Page Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/app/app.dub.co/(auth)/forgot-password/page.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Replaced Next.js metadata with Inertia Head
 * - Integrated with Laravel password reset system
 * - Maintained exact visual consistency with dub-main
 */

import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { Button, Input, Label } from '@/components/ui';
import { FormEventHandler } from 'react';

interface ForgotPasswordProps {
  status?: string;
}

export default function ForgotPassword({ status }: ForgotPasswordProps) {
  const { data, setData, post, processing, errors } = useForm({
    email: '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    post(route('password.email'));
  };

  return (
    <AuthLayout>
      <Head title="Forgot Password" />

      <div className="w-full max-w-sm">
        <h3 className="text-center text-xl font-semibold">
          Reset your password
        </h3>

        <p className="mt-4 text-center text-sm text-gray-600">
          Enter your email address and we'll send you a link to reset your password.
        </p>

        {status && (
          <div className="mb-4 mt-4 text-sm font-medium text-green-600">
            {status}
          </div>
        )}

        <div className="mt-8">
          <form onSubmit={submit} className="space-y-4">
            <div>
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                name="email"
                value={data.email}
                className="mt-1 block w-full"
                placeholder="Enter your email"
                onChange={(e) => setData('email', e.target.value)}
                error={errors.email}
                required
              />
            </div>

            <Button
              type="submit"
              variant="primary"
              loading={processing}
              text="Send reset link"
              className="w-full"
            />
          </form>
        </div>

        <p className="mt-6 text-center text-sm font-medium text-gray-500">
          Remember your password?&nbsp;
          <Link
            href={route('login')}
            className="font-semibold text-gray-700 transition-colors hover:text-gray-900"
          >
            Back to login
          </Link>
        </p>
      </div>
    </AuthLayout>
  );
}
