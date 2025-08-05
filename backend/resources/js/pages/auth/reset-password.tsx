/**
 * Reset Password Page Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/app/app.dub.co/(auth)/auth/reset-password
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Replaced Next.js metadata with Inertia Head
 * - Integrated with Laravel password reset system
 * - Maintained exact visual consistency with dub-main
 */

import { Head, useForm } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { Button, Input, Label } from '@/components/ui';
import { FormEventHandler, useEffect } from 'react';

interface ResetPasswordProps {
  token: string;
  email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
  const { data, setData, post, processing, errors, reset } = useForm({
    token: token,
    email: email,
    password: '',
    password_confirmation: '',
  });

  useEffect(() => {
    return () => {
      reset('password', 'password_confirmation');
    };
  }, []);

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    post(route('password.store'));
  };

  return (
    <AuthLayout>
      <Head title="Reset Password" />

      <div className="w-full max-w-sm">
        <h3 className="text-center text-xl font-semibold">
          Set new password
        </h3>

        <p className="mt-4 text-center text-sm text-gray-600">
          Enter your new password below.
        </p>

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
                autoComplete="username"
                onChange={(e) => setData('email', e.target.value)}
                error={errors.email}
                required
                readOnly
              />
            </div>

            <div>
              <Label htmlFor="password">New Password</Label>
              <Input
                id="password"
                type="password"
                name="password"
                value={data.password}
                className="mt-1 block w-full"
                autoComplete="new-password"
                placeholder="Enter your new password"
                onChange={(e) => setData('password', e.target.value)}
                error={errors.password}
                required
              />
            </div>

            <div>
              <Label htmlFor="password_confirmation">Confirm Password</Label>
              <Input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                value={data.password_confirmation}
                className="mt-1 block w-full"
                autoComplete="new-password"
                placeholder="Confirm your new password"
                onChange={(e) => setData('password_confirmation', e.target.value)}
                error={errors.password_confirmation}
                required
              />
            </div>

            <Button
              type="submit"
              variant="primary"
              loading={processing}
              text="Reset password"
              className="w-full"
            />
          </form>
        </div>
      </div>
    </AuthLayout>
  );
}
