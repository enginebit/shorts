/**
 * Login Page Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/app/app.dub.co/(auth)/login/page.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Replaced Next.js metadata with Inertia Head
 * - Integrated with Laravel Sanctum authentication
 * - Maintained exact visual consistency with dub-main
 */

import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { Button, Input, Label } from '@/components/ui';
import { FormEventHandler, useEffect, useState } from 'react';

interface LoginProps {
  canResetPassword: boolean;
  status?: string;
}

export default function Login({ canResetPassword, status }: LoginProps) {
  const { data, setData, post, processing, errors, reset } = useForm({
    email: '',
    password: '',
    remember: false,
  });

  const [showPassword, setShowPassword] = useState(false);

  useEffect(() => {
    return () => {
      reset('password');
    };
  }, []);

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    post(route('login'));
  };

  return (
    <AuthLayout showTerms>
      <Head title="Log in to your Shorts account" />

      <div className="w-full max-w-sm">
        <h3 className="text-center text-xl font-semibold">
          Log in to your Shorts account
        </h3>

        {status && (
          <div className="mb-4 text-sm font-medium text-green-600">
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
                autoComplete="username"
                placeholder="Enter your email"
                onChange={(e) => setData('email', e.target.value)}
                error={errors.email}
                required
              />
            </div>

            <div>
              <Label htmlFor="password">Password</Label>
              <Input
                id="password"
                type="password"
                name="password"
                value={data.password}
                className="mt-1 block w-full"
                autoComplete="current-password"
                placeholder="Enter your password"
                onChange={(e) => setData('password', e.target.value)}
                error={errors.password}
                required
              />
            </div>

            <div className="flex items-center justify-between">
              <label className="flex items-center">
                <input
                  type="checkbox"
                  name="remember"
                  checked={data.remember}
                  onChange={(e) => setData('remember', e.target.checked)}
                  className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                />
                <span className="ml-2 text-sm text-gray-600">Remember me</span>
              </label>

              {canResetPassword && (
                <Link
                  href={route('password.request')}
                  className="text-sm text-gray-600 underline hover:text-gray-900"
                >
                  Forgot your password?
                </Link>
              )}
            </div>

            <Button
              type="submit"
              variant="primary"
              loading={processing}
              text="Log in"
              className="w-full"
            />
          </form>
        </div>

        <p className="mt-6 text-center text-sm font-medium text-gray-500">
          Don't have an account?&nbsp;
          <Link
            href={route('register')}
            className="font-semibold text-gray-700 transition-colors hover:text-gray-900"
          >
            Sign up
          </Link>
        </p>
      </div>
    </AuthLayout>
  );
}
