/**
 * ResetPasswordForm Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/auth/reset-password-form.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced react-hook-form with Inertia.js useForm
 * - Replaced Next.js router with Inertia navigation
 * - Replaced toast notifications with simple alerts (can be enhanced later)
 * - Maintained exact visual consistency with dub-main
 */

import { Button, Input } from '@/components/ui';
import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';

interface ResetPasswordFormProps {
  token: string;
  email: string;
}

export const ResetPasswordForm = ({ token, email }: ResetPasswordFormProps) => {
  const { data, setData, post, processing, errors } = useForm({
    token,
    email,
    password: '',
    password_confirmation: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    post(route('password.store'), {
      onSuccess: () => {
        // In a real implementation, you'd use a toast notification
        alert('Your password has been reset. You can now log in with your new password.');
      },
      onError: (errors) => {
        // Handle validation errors
        if (errors.password) {
          alert(errors.password);
        } else if (errors.password_confirmation) {
          alert(errors.password_confirmation);
        } else if (errors.token) {
          alert('The password reset token is invalid or expired.');
        }
      },
    });
  };

  return (
    <>
      <form className="flex w-full flex-col gap-6" onSubmit={handleSubmit}>
        <input type="hidden" value={token} name="token" />
        <input type="hidden" value={email} name="email" />

        <label>
          <span className="text-content-emphasis mb-2 block text-sm font-medium leading-none">
            Password
          </span>
          <Input
            type="password"
            value={data.password}
            onChange={(e) => setData('password', e.target.value)}
            required
            autoComplete="new-password"
            placeholder="Enter your new password"
            error={errors.password}
            minLength={8}
          />
        </label>

        <label>
          <span className="text-content-emphasis mb-2 block text-sm font-medium leading-none">
            Confirm password
          </span>
          <Input
            type="password"
            value={data.password_confirmation}
            onChange={(e) => setData('password_confirmation', e.target.value)}
            required
            autoComplete="new-password"
            placeholder="Confirm your new password"
            error={errors.password_confirmation}
          />
        </label>

        <Button
          text="Reset Password"
          type="submit"
          loading={processing}
          disabled={processing}
        />
      </form>
    </>
  );
};
