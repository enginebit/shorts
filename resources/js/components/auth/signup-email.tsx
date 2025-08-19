/**
 * SignUpEmail Component
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/auth/register/signup-email.tsx
 *
 * Adaptations for Laravel + Inertia.js:
 * - Replaced react-hook-form with Inertia.js useForm
 * - Simplified validation to use Laravel backend validation
 * - Maintained exact visual consistency with dub-main
 * - Progressive form disclosure (email first, then password)
 */

import { Button, Input } from '@/components/ui';
import { useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { route } from 'ziggy-js';
import { useRegisterContext } from '@/contexts/register-context';
import { toast } from 'sonner';

export const SignUpEmail = () => {
  const [showPassword, setShowPassword] = useState(false);
  const { setStep, setEmail, setPassword, email } = useRegisterContext();

  const { data, setData, post, processing, errors } = useForm({
    email: email || '',
    password: '',
  });

  const onSubmit = (e: FormEvent) => {
    e.preventDefault();

    // Progressive form disclosure - show password field if email is entered
    if (data.email && !data.password && !showPassword) {
      setShowPassword(true);
      return;
    }

    // Send OTP for email verification
    post(route('auth.send-otp'), {
      email: data.email,
      password: data.password,
      onSuccess: () => {
        setEmail(data.email);
        setPassword(data.password);
        setStep('verify');
        toast.success('Verification code sent to your email!');
      },
      onError: (errors) => {
        toast.error(errors.email || errors.password || 'Failed to send verification code');
      },
    });
  };

  return (
    <form onSubmit={onSubmit}>
      <div className="flex flex-col gap-y-6">
        <label>
          <span className="text-content-emphasis mb-2 block text-sm font-medium leading-none">
            Email
          </span>
          <Input
            type="email"
            placeholder="panic@thedis.co"
            autoComplete="email"
            required
            autoFocus={!showPassword}
            value={data.email}
            onChange={(e) => setData('email', e.target.value)}
            error={errors.email}
          />
        </label>

        {showPassword && (
          <label>
            <span className="text-content-emphasis mb-2 block text-sm font-medium leading-none">
              Password
            </span>
            <Input
              type="password"
              required
              autoFocus
              value={data.password}
              onChange={(e) => setData('password', e.target.value)}
              error={errors.password}
              minLength={8}
              placeholder="Password (min. 8 characters)"
            />
          </label>
        )}

        <Button
          type="submit"
          text={processing ? 'Creating account...' : showPassword ? 'Create account' : 'Continue'}
          disabled={processing}
          loading={processing}
        />
      </div>
    </form>
  );
};
