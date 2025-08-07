/**
 * Forgot Password Page Component
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/app/app.dub.co/(auth)/forgot-password/page.tsx
 *
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Replaced Next.js metadata with Inertia Head
 * - Integrated with Laravel password reset system
 * - Uses new ForgotPasswordForm component matching dub-main
 * - Maintained exact visual consistency with dub-main
 */

import { Head, Link } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { route } from 'ziggy-js';
import { ForgotPasswordForm } from '@/components/auth/forgot-password-form';

interface ForgotPasswordProps {
  status?: string;
  email?: string;
}

export default function ForgotPassword({ status, email }: ForgotPasswordProps) {

  return (
    <AuthLayout>
      <Head title="Reset your password" />

      <div className="w-full max-w-sm">
        <h3 className="text-center text-xl font-semibold">
          Reset your password
        </h3>

        <div className="mt-8">
          <ForgotPasswordForm email={email} />
        </div>

        <p className="mt-6 text-center text-sm font-medium text-neutral-500">
          Remember your password?&nbsp;
          <Link
            href={route('login')}
            className="font-semibold text-neutral-700 transition-colors hover:text-neutral-900"
          >
            Back to login
          </Link>
        </p>
      </div>
    </AuthLayout>
  );
}
