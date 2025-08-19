/**
 * Register Page Component
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/app/app.dub.co/(auth)/register/page-client.tsx
 *
 * Key Patterns Adopted:
 * - Two-step registration flow (signup → verify)
 * - RegisterProvider context for state management
 * - Progressive form disclosure
 * - Exact visual consistency with dub-main
 *
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Replaced Next.js metadata with Inertia Head
 * - Integrated with Laravel registration system
 * - Uses new SignUpForm and VerifyEmailForm components
 */

import { Head, Link } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { route } from 'ziggy-js';
import { SignUpForm } from '@/components/auth/signup-form';
import { VerifyEmailForm } from '@/components/auth/verify-email-form';
import { AuthAlternativeBanner } from '@/components/auth/auth-alternative-banner';
import { RegisterProvider, useRegisterContext } from '@/contexts/register-context';
import { truncate } from '@/lib/utils';

export default function Register() {
  return (
    <AuthLayout showTerms>
      <Head title="Create your Shorts account" />

      <RegisterProvider>
        <RegisterFlow />
      </RegisterProvider>
    </AuthLayout>
  );
}

function SignUp() {
  return (
    <>
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
    </>
  );
}

function Verify() {
  const { email } = useRegisterContext();

  return (
    <>
      <div className="w-full max-w-sm">
        <div className="flex flex-col items-center gap-1 text-center">
          <h3 className="text-center text-xl font-semibold">
            Verify your email address
          </h3>
          <p className="text-base font-medium text-neutral-500">
            Enter the six digit verification code sent to{' '}
            <strong className="font-semibold text-neutral-600" title={email}>
              {truncate(email, 30)}
            </strong>
          </p>
        </div>
        <div className="mt-12">
          <VerifyEmailForm />
        </div>
      </div>
    </>
  );
}

const RegisterFlow = () => {
  const { step } = useRegisterContext();

  if (step === 'signup') return <SignUp />;
  if (step === 'verify') return <Verify />;
};
