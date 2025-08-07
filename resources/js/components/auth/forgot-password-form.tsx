/**
 * ForgotPasswordForm Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/auth/forgot-password-form.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced useAction with Inertia.js useForm
 * - Replaced Next.js router with Inertia navigation
 * - Replaced toast notifications with simple alerts (can be enhanced later)
 * - Maintained exact visual consistency with dub-main
 */

import { Button, Input } from '@/components/ui';
import { useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { route } from 'ziggy-js';

interface ForgotPasswordFormProps {
  email?: string;
}

export const ForgotPasswordForm = ({ email: initialEmail }: ForgotPasswordFormProps = {}) => {
  const [email, setEmail] = useState(initialEmail || '');

  const { data, setData, post, processing, errors } = useForm({
    email: initialEmail || '',
  });

  useEffect(() => {
    setData('email', email);
  }, [email, setData]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    post(route('password.email'), {
      onSuccess: () => {
        // In a real implementation, you'd use a toast notification
        alert('You will receive an email with instructions to reset your password.');
      },
      onError: (errors) => {
        // Handle validation errors
        if (errors.email) {
          alert(errors.email);
        }
      },
    });
  };

  return (
    <div className="flex w-full flex-col gap-3">
      <form onSubmit={handleSubmit}>
        <div className="flex flex-col gap-6">
          <label>
            <span className="text-content-emphasis mb-2 block text-sm font-medium leading-none">
              Email
            </span>
            <Input
              type="email"
              autoFocus
              value={email}
              placeholder="panic@thedis.co"
              onChange={(e) => setEmail(e.target.value)}
              error={errors.email}
              required
            />
          </label>
          <Button
            type="submit"
            text={processing ? 'Sending...' : 'Send reset link'}
            loading={processing}
            disabled={email.length < 3 || processing}
          />
        </div>
      </form>
    </div>
  );
};
