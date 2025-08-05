/**
 * Register Page Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/app/app.dub.co/(auth)/register/page-client.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Replaced Next.js metadata with Inertia Head
 * - Integrated with Laravel registration system
 * - Simplified from multi-step flow to single registration form
 * - Maintained exact visual consistency with dub-main
 */

import { Head, Link, useForm } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { Button, Input, Label } from '@/components/ui';
import { FormEventHandler, useEffect } from 'react';

export default function Register() {
  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    email: '',
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

    post(route('register'));
  };

  return (
    <AuthLayout showTerms>
      <Head title="Create your Shorts account" />

      <div className="w-full max-w-sm">
        <h3 className="text-center text-xl font-semibold">
          Create your Shorts account
        </h3>

        <div className="mt-8">
          <form onSubmit={submit} className="space-y-4">
            <div>
              <Label htmlFor="name">Full Name</Label>
              <Input
                id="name"
                name="name"
                value={data.name}
                className="mt-1 block w-full"
                autoComplete="name"
                placeholder="Enter your full name"
                onChange={(e) => setData('name', e.target.value)}
                error={errors.name}
                required
              />
            </div>

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
                autoComplete="new-password"
                placeholder="Create a password"
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
                placeholder="Confirm your password"
                onChange={(e) => setData('password_confirmation', e.target.value)}
                error={errors.password_confirmation}
                required
              />
            </div>

            <Button
              type="submit"
              variant="primary"
              loading={processing}
              text="Create account"
              className="w-full"
            />
          </form>
        </div>

        <p className="mt-6 text-center text-sm font-medium text-gray-500">
          Already have an account?&nbsp;
          <Link
            href={route('login')}
            className="font-semibold text-gray-700 transition-colors hover:text-gray-900"
          >
            Log in
          </Link>
        </p>
      </div>
    </AuthLayout>
  );
}
