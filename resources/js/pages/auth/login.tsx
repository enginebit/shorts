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
 * - Uses new LoginForm component structure matching dub-main
 */

import { Head, Link } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { route } from 'ziggy-js';
import LoginForm from '@/components/auth/login-form';
import { AuthAlternativeBanner } from '@/components/auth/auth-alternative-banner';

interface LoginProps {
  canResetPassword: boolean;
  status?: string;
}

export default function Login({ canResetPassword, status }: LoginProps) {

  return (
    <AuthLayout showTerms>
      <Head title="Log in to your Shorts account" />

      <div className="w-full max-w-sm">
        <h3 className="text-center text-xl font-semibold">
          Log in to your Shorts account
        </h3>
        <div className="mt-8">
          <LoginForm />
        </div>
        <p className="mt-6 text-center text-sm font-medium text-neutral-500">
          Don't have an account?&nbsp;
          <Link
            href={route('register')}
            className="font-semibold text-neutral-700 transition-colors hover:text-neutral-900"
          >
            Sign up
          </Link>
        </p>

        <div className="mt-12 w-full">
          <AuthAlternativeBanner
            text="Looking for your Shorts partner account?"
            cta="Log in at partners.shorts.co"
            href="https://partners.shorts.co/login"
          />
        </div>
      </div>
    </AuthLayout>
  );
}
