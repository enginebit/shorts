/**
 * Register Page Component
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/app/app.dub.co/(auth)/register/page-client.tsx
 *
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Replaced Next.js metadata with Inertia Head
 * - Integrated with Laravel registration system
 * - Uses new SignUpForm component structure matching dub-main
 * - Maintained exact visual consistency with dub-main
 */

import { Head, Link } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { route } from 'ziggy-js';
import { SignUpForm } from '@/components/auth/signup-form';
import { AuthAlternativeBanner } from '@/components/auth/auth-alternative-banner';

export default function Register() {

  return (
    <AuthLayout showTerms>
      <Head title="Create your Shorts account" />

      <div className="w-full max-w-sm">
        <h3 className="text-center text-xl font-semibold">
          Create your Shorts account
        </h3>
        <div className="mt-8">
          <SignUpForm />
        </div>
        <p className="mt-6 text-center text-sm font-medium text-neutral-500">
          Already have an account?&nbsp;
          <Link
            href={route('login')}
            className="font-semibold text-neutral-700 transition-colors hover:text-neutral-900"
          >
            Log in
          </Link>
        </p>

        <div className="mt-12 w-full">
          <AuthAlternativeBanner
            text="Looking for your Shorts partner account?"
            cta="Sign up at partners.shorts.co"
            href="https://partners.shorts.co/register"
          />
        </div>
      </div>
    </AuthLayout>
  );
}
