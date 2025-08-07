/**
 * Reset Password Page Component
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/app/app.dub.co/(auth)/auth/reset-password
 *
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Replaced Next.js metadata with Inertia Head
 * - Integrated with Laravel password reset system
 * - Uses new ResetPasswordForm component matching dub-main
 * - Maintained exact visual consistency with dub-main
 */

import { Head } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import { ResetPasswordForm } from '@/components/auth/reset-password-form';

interface ResetPasswordProps {
  token: string;
  email: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {

  return (
    <AuthLayout>
      <Head title="Reset your password" />

      <div className="w-full max-w-sm">
        <h3 className="text-center text-xl font-semibold">
          Reset your password
        </h3>
        <div className="mt-8">
          <ResetPasswordForm token={token} email={email} />
        </div>
      </div>
    </AuthLayout>
  );
}
