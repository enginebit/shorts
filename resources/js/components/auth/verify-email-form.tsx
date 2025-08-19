/**
 * VerifyEmailForm Component
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/auth/register/verify-email-form.tsx
 *
 * Key Patterns Adopted:
 * - 6-digit OTP input with individual character slots
 * - Auto-focus and keyboard navigation
 * - Error handling with visual feedback
 * - Resend OTP functionality
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia.js useForm instead of next-safe-action
 * - Integrates with Laravel OTP verification endpoint
 * - Maintains exact visual consistency with dub-main
 */

import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui';
import { useRegisterContext } from '@/contexts/register-context';
import { route } from 'ziggy-js';
import { toast } from 'sonner';

export const VerifyEmailForm = () => {
  const [code, setCode] = useState('');
  const [isInvalidCode, setIsInvalidCode] = useState(false);
  const { email, password } = useRegisterContext();

  const { data, setData, post, processing } = useForm({
    email: email || '',
    password: password || '',
    code: '',
  });

  const resendForm = useForm({
    email: email || '',
    password: password || '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (code.length !== 6) {
      setIsInvalidCode(true);
      return;
    }

    // Update form data with current code
    setData('code', code);

    post(route('auth.verify-otp'), {
      onSuccess: () => {
        toast.success('Account created! Redirecting to onboarding...');
        // Redirect will be handled by the backend
      },
      onError: (errors) => {
        toast.error(errors.code || 'Invalid verification code');
        setCode('');
        setIsInvalidCode(true);
      },
    });
  };

  const handleCodeChange = (value: string) => {
    setIsInvalidCode(false);
    setCode(value.replace(/\D/g, '').slice(0, 6));
  };

  const handleResendOtp = () => {
    resendForm.post(route('auth.send-otp'), {
      onSuccess: () => {
        toast.success('Verification code sent!');
      },
      onError: (errors) => {
        toast.error(errors.email || 'Failed to send verification code');
      },
    });
  };

  if (!email || !password) {
    window.location.href = '/register';
    return null;
  }

  return (
    <div className="flex flex-col gap-3">
      <form onSubmit={handleSubmit}>
        <div className="mb-6">
          <div className="flex w-full items-center justify-between gap-2">
            {Array.from({ length: 6 }).map((_, idx) => (
              <input
                key={idx}
                type="text"
                inputMode="numeric"
                maxLength={1}
                value={code[idx] || ''}
                onChange={(e) => {
                  const newCode = code.split('');
                  newCode[idx] = e.target.value.replace(/\D/g, '');
                  const updatedCode = newCode.join('');
                  handleCodeChange(updatedCode);

                  // Auto-focus next input
                  if (e.target.value && idx < 5) {
                    const nextInput = e.target.parentElement?.children[idx + 1] as HTMLInputElement;
                    nextInput?.focus();
                  }
                }}
                onKeyDown={(e) => {
                  // Handle backspace
                  if (e.key === 'Backspace' && !code[idx] && idx > 0) {
                    const prevInput = e.target.parentElement?.children[idx - 1] as HTMLInputElement;
                    prevInput?.focus();
                  }
                }}
                className={`
                  relative flex h-14 w-12 items-center justify-center text-xl text-center
                  rounded-lg border bg-white ring-0 transition-all
                  ${isInvalidCode
                    ? 'border-red-300 ring-2 ring-red-100'
                    : 'border-neutral-200 focus:border-neutral-800 focus:ring-2 focus:ring-neutral-200'
                  }
                `}
                autoFocus={idx === 0}
              />
            ))}
          </div>

          {isInvalidCode && (
            <p className="mt-2 text-sm text-red-600">
              Invalid verification code. Please try again.
            </p>
          )}
        </div>

        <Button
          type="submit"
          text={processing ? 'Verifying...' : 'Verify email'}
          disabled={processing || code.length !== 6}
          loading={processing}
          className="w-full"
        />
      </form>

      <div className="text-center">
        <button
          type="button"
          onClick={handleResendOtp}
          disabled={processing || resendForm.processing}
          className="text-sm text-neutral-500 hover:text-neutral-700 disabled:opacity-50"
        >
          {resendForm.processing ? 'Sending...' : "Didn't receive a code? Resend"}
        </button>
      </div>
    </div>
  );
};
