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
import { Link, useForm } from '@inertiajs/react';
import { useContext, useState } from 'react';
import { route } from 'ziggy-js';
import { errorCodes, LoginFormContext } from './login-form';

export const EmailSignIn = ({ next }: { next?: string }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const {
    showPasswordField,
    setShowPasswordField,
    setClickedMethod,
    authMethod,
    clickedMethod,
    setLastUsedAuthMethod,
  } = useContext(LoginFormContext);

  const { data, setData, post, processing, errors } = useForm({
    email: '',
    password: '',
    remember: false,
  });

  const [isCheckingAccount, setIsCheckingAccount] = useState(false);

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

  return (
    <>
      <form
        onSubmit={async (e) => {
          e.preventDefault();

          // Check if the user can enter a password, and if so display the field
          if (!showPasswordField) {
            const result = await checkAccountExists(email);

            if (result.accountExists && result.hasPassword) {
              setShowPasswordField(true);
              setData('email', email);
              return;
            }

            if (!result.accountExists) {
              setClickedMethod(undefined);
              // In a real implementation, you'd use a toast notification
              alert('No account found with that email address.');
              return;
            }
          }

          setClickedMethod('email');

          // Submit the form using Inertia
          setData({
            email,
            password,
            remember: false,
          });

          post(route('login'), {
            onSuccess: () => {
              setLastUsedAuthMethod('email');
              if (typeof window !== 'undefined') {
                localStorage.setItem('last-used-auth-method', 'email');
              }
            },
            onError: (errors) => {
              setClickedMethod(undefined);
              // Handle errors - in a real implementation, you'd use toast notifications
              if (errors.email) {
                alert(errors.email);
              } else if (errors.password) {
                alert(errors.password);
              }
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
            <input
              id="email"
              name="email"
              autoFocus={!showPasswordField}
              type="email"
              placeholder="panic@thedis.co"
              autoComplete="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              size={1}
              className={cn(
                'block w-full min-w-0 appearance-none rounded-md border border-neutral-300 px-3 py-2 placeholder-neutral-400 shadow-sm focus:border-black focus:outline-none focus:ring-black sm:text-sm',
                {
                  'pr-10': isCheckingAccount,
                },
              )}
            />
          </label>
        )}

        {showPasswordField && (
          <label>
            <div className="mb-2 flex items-center justify-between">
              <span className="text-content-emphasis block text-sm font-medium leading-none">
                Password
              </span>
              <Link
                href={route('password.request', { email: encodeURIComponent(email) })}
                className="text-content-subtle hover:text-content-emphasis text-xs leading-none underline underline-offset-2 transition-colors"
              >
                Forgot password?
              </Link>
            </div>
            <Input
              type="password"
              autoFocus
              value={password}
              placeholder="Password"
              onChange={(e) => setPassword(e.target.value)}
            />
          </label>
        )}

        <Button
          text={`Log in with ${password ? 'password' : 'email'}`}
          {...(authMethod !== 'email' && {
            type: 'button',
            onClick: (e) => {
              e.preventDefault();
              // setAuthMethod('email');
            },
          })}
          loading={clickedMethod === 'email' || isCheckingAccount || processing}
          disabled={clickedMethod && clickedMethod !== 'email'}
        />
      </form>
    </>
  );
};
