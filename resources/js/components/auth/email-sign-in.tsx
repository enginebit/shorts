/**
 * EmailSignIn Component
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/auth/login/email-sign-in.tsx
 *
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next-Auth signIn with Inertia.js form submission
 * - Replaced useAction with direct API calls to Laravel backend
 * - Simplified account existence checking for initial implementation
 * - Maintained exact visual consistency with dub-main
 */

import { Button, Input } from '@/components/ui';
import { cn } from '@/lib/utils';
import { Link, useForm, usePage, router } from '@inertiajs/react';
import { useContext, useState, useEffect, useRef } from 'react';
import { route } from 'ziggy-js';
import { toast } from 'sonner';
import { debugCSRFTokenState } from '@/lib/csrf-utils';
import { errorCodes, LoginFormContext } from './login-form';

export const EmailSignIn = ({ next }: { next?: string }) => {
  const {
    showPasswordField,
    setShowPasswordField,
    setClickedMethod,
    authMethod,
    clickedMethod,
    setLastUsedAuthMethod,
  } = useContext(LoginFormContext);

  const { data, setData, errors } = useForm({
    email: '',
    password: '',
    remember: false,
  });

  const [isCheckingAccount, setIsCheckingAccount] = useState(false);
  const [autoFillDetected, setAutoFillDetected] = useState(false);
  const passwordInputRef = useRef<HTMLInputElement>(null);

  // Helper function to get the actual password value (handles auto-fill)
  const getActualPassword = (): string => {
    if (passwordInputRef.current) {
      const inputValue = passwordInputRef.current.value;
      if (inputValue) {
        return inputValue;
      }
    }
    return data.password;
  };

  // Auto-fill detection for password managers
  useEffect(() => {
    if (!showPasswordField || !passwordInputRef.current) return;

    const passwordInput = passwordInputRef.current;

    // Function to check and sync auto-filled password
    const checkAutoFill = () => {
      const currentValue = passwordInput.value;
      if (currentValue && currentValue !== data.password) {
        console.log('Auto-fill detected, syncing password to form state:', currentValue.length, 'characters');
        setData('password', currentValue);
        setAutoFillDetected(true);

        // Clear the indicator after a few seconds
        setTimeout(() => setAutoFillDetected(false), 3000);
      }
    };

    // Multiple strategies to detect auto-fill:

    // 1. Immediate check (for instant auto-fill)
    const immediateCheck = setTimeout(checkAutoFill, 100);

    // 2. Delayed check (for slower auto-fill)
    const delayedCheck = setTimeout(checkAutoFill, 500);

    // 3. Animation frame check (for CSS-based auto-fill detection)
    const animationCheck = requestAnimationFrame(() => {
      setTimeout(checkAutoFill, 0);
    });

    // 4. Event listeners for various auto-fill scenarios
    const events = ['input', 'change', 'blur', 'focus', 'animationstart', 'animationend'];

    const handleAutoFillEvent = (e: Event) => {
      // Check if this is our auto-fill detection animation
      if (e.type === 'animationstart' || e.type === 'animationend') {
        const animationEvent = e as AnimationEvent;
        if (animationEvent.animationName === 'autofill-detected') {
          console.log('CSS auto-fill animation detected');
          setTimeout(checkAutoFill, 10);
          return;
        }
      }

      // Small delay to ensure the value is set
      setTimeout(checkAutoFill, 10);
    };

    events.forEach(event => {
      passwordInput.addEventListener(event, handleAutoFillEvent);
    });

    // 5. Mutation observer to detect DOM changes (for advanced password managers)
    const observer = new MutationObserver(() => {
      checkAutoFill();
    });

    observer.observe(passwordInput, {
      attributes: true,
      attributeFilter: ['value'],
    });

    // 6. Periodic check while field is visible (fallback)
    const intervalCheck = setInterval(checkAutoFill, 1000);

    // Cleanup
    return () => {
      clearTimeout(immediateCheck);
      clearTimeout(delayedCheck);
      cancelAnimationFrame(animationCheck);
      clearInterval(intervalCheck);

      events.forEach(event => {
        passwordInput.removeEventListener(event, handleAutoFillEvent);
      });

      observer.disconnect();
    };
  }, [showPasswordField, data.password, setData]);

  // Simplified account existence check - in a real implementation,
  // you'd want to create an API endpoint for this
  const checkAccountExists = async (email: string) => {
    setIsCheckingAccount(true);
    try {
      // For now, we'll assume all accounts exist and have passwords
      // In a real implementation, you'd call your Laravel API
      return { accountExists: true, hasPassword: true };
    } catch (error) {
      return { accountExists: false, hasPassword: false };
    } finally {
      setIsCheckingAccount(false);
    }
  };

  const page = usePage();
  const csrfToken = (page.props as any)?.csrf_token ?? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const [submitting, setSubmitting] = useState(false);

  return (
    <>
      <form
        onSubmit={async (e) => {
          e.preventDefault();

          // Check if the user can enter a password, and if so display the field
          if (!showPasswordField) {
            const result = await checkAccountExists(data.email);

            if (result.accountExists && result.hasPassword) {
              setShowPasswordField(true);
              return;
            }

            if (!result.accountExists) {
              setClickedMethod(undefined);
              toast.error('No account found with that email address.');
              return;
            }
          }

          setClickedMethod('email');

          // Get the actual password value (handles auto-fill)
          const actualPassword = getActualPassword();

          // Debug: Log form data before submission (SECURE - no sensitive data)
          console.log('Login form data:', {
            email: data.email,
            passwordLength: actualPassword.length,
            hasPassword: !!actualPassword,
            formStatePasswordLength: data.password.length,
            inputHasValue: !!passwordInputRef.current?.value
          });

          // Debug CSRF token state before submission
          debugCSRFTokenState();

          // Ensure we have a password
          if (!actualPassword) {
            toast.error('Password is required');
            setClickedMethod(undefined);
            return;
          }

          // Submit via Inertia router with explicit payload and CSRF token
          setSubmitting(true);
          router.visit(route('login'), {
            method: 'post',
            data: {
              email: data.email,
              password: actualPassword,
              remember: data.remember,
              _token: csrfToken,
            },
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'X-XSRF-TOKEN': csrfToken,
            },
            preserveScroll: true,
            onSuccess: () => {
              setLastUsedAuthMethod('email');
              if (typeof window !== 'undefined') {
                localStorage.setItem('last-used-auth-method', 'email');
              }
            },
            onError: (errors) => {
              setClickedMethod(undefined);
              if ((errors as any).email) {
                toast.error((errors as any).email as string);
              } else if ((errors as any).password) {
                toast.error((errors as any).password as string);
              } else {
                toast.error('Login failed. Please check your credentials.');
              }
            },
            onFinish: () => {
              setSubmitting(false);
            },
          });
        }}
        className="flex flex-col gap-y-6"
      >
        {authMethod === 'email' && (
          <label>
            <span className="text-content-emphasis mb-2 block text-sm font-medium leading-none">
              Email
            </span>
            <Input
              id="email"
              name="email"
              autoFocus={!showPasswordField}
              type="email"
              placeholder="panic@thedis.co"
              autoComplete="email"
              required
              value={data.email}
              onChange={(e) => setData('email', e.target.value)}
              error={errors.email}
            />
          </label>
        )}

        {showPasswordField && (
          <label>
            <div className="mb-2 flex items-center justify-between">
              <span className="text-content-emphasis block text-sm font-medium leading-none">
                Password
                {autoFillDetected && (
                  <span className="ml-2 text-xs text-green-600 font-normal">
                    ✓ Auto-fill detected
                  </span>
                )}
              </span>
              <Link
                href={route('password.request', { email: encodeURIComponent(data.email) })}
                className="text-content-subtle hover:text-content-emphasis text-xs leading-none underline underline-offset-2 transition-colors"
              >
                Forgot password?
              </Link>
            </div>
            <Input
              ref={passwordInputRef}
              type="password"
              autoFocus
              value={data.password}
              placeholder="Password"
              autoComplete="current-password"
              onChange={(e) => setData('password', e.target.value)}
              onInput={(e) => {
                // Additional handler for auto-fill detection
                const target = e.target as HTMLInputElement;
                if (target.value !== data.password) {
                  setData('password', target.value);
                }
              }}
              onBlur={(e) => {
                // Sync on blur (when user clicks away)
                const target = e.target as HTMLInputElement;
                if (target.value !== data.password) {
                  console.log('Password synced on blur:', target.value.length, 'characters');
                  setData('password', target.value);
                }
              }}
              onFocus={(e) => {
                // Check for auto-fill when field gains focus
                const target = e.target as HTMLInputElement;
                setTimeout(() => {
                  if (target.value !== data.password) {
                    console.log('Password synced on focus:', target.value.length, 'characters');
                    setData('password', target.value);
                  }
                }, 100);
              }}
              error={errors.password}
            />
          </label>
        )}

        <Button
          text={`Log in with ${data.password ? 'password' : 'email'}`}
          {...(authMethod !== 'email' && {
            type: 'button',
            onClick: (e) => {
              e.preventDefault();
              // setAuthMethod('email');
            },
          })}
          loading={clickedMethod === 'email' || isCheckingAccount || submitting}
          disabled={clickedMethod && clickedMethod !== 'email'}
        />
      </form>
    </>
  );
};
