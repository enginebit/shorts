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

import { Head, Link, usePage } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { route } from 'ziggy-js';
import LoginForm from '@/components/auth/login-form';
import { AuthAlternativeBanner } from '@/components/auth/auth-alternative-banner';
import Logo from '@/components/shared/logo';

interface LoginProps {
  canResetPassword: boolean;
  status?: string;
}

export default function Login({ canResetPassword, status }: LoginProps) {

  return (
    <AuthLayout showTerms>
      <Head title="Log in to brachy.io" />

      <div className="w-full max-w-sm">
        <div className="mt-2 flex items-center justify-center gap-4">
          <Logo variant="icon" size="lg" />
          <Logo variant="text" size="lg" />
        </div>
        <div className="mt-8">
          <LoginForm />
        </div>
        <p className="mt-6 text-center text-sm font-medium text-[rgb(var(--content-subtle))]">
          Don't have an account?&nbsp;
          <Link
            href={route('register')}
            className="font-semibold text-[rgb(var(--content-default))] transition-colors hover:text-[rgb(var(--content-emphasis))]"
          >
            Sign up
          </Link>
        </p>

        <div className="mt-12 w-full">
          <AuthAlternativeBanner
            text="Looking for your branchy.io partner account?"
            cta="Log in at partners.brachy.io"
            href="https://partners.brachy.io/login"
          />
        </div>
      </div>
    </AuthLayout>
  );
}
