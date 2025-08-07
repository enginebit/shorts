/**
 * LoginForm Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/auth/login/login-form.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next-Auth with Laravel Sanctum authentication
 * - Simplified to email/password authentication for initial implementation
 * - Maintained exact visual consistency with dub-main
 * - Uses Inertia.js form handling instead of Next.js patterns
 */

import { AnimatedSizeContainer, Button } from '@/components/ui';
import { useForm } from '@inertiajs/react';
import {
  ComponentType,
  Dispatch,
  SetStateAction,
  createContext,
  useEffect,
  useState,
} from 'react';
import { route } from 'ziggy-js';
import { AuthMethodsSeparator } from './auth-methods-separator';
import { EmailSignIn } from './email-sign-in';

export const authMethods = [
  'email',
  'password',
] as const;

export type AuthMethod = (typeof authMethods)[number];

export const errorCodes = {
  'no-credentials': 'Please provide an email and password.',
  'invalid-credentials': 'Email or password is incorrect.',
  'exceeded-login-attempts':
    'Account has been locked due to too many login attempts. Please contact support to unlock your account.',
  'too-many-login-attempts': 'Too many login attempts. Please try again later.',
  'email-not-verified': 'Please verify your email address.',
};

export const LoginFormContext = createContext<{
  authMethod: AuthMethod | undefined;
  setAuthMethod: Dispatch<SetStateAction<AuthMethod | undefined>>;
  clickedMethod: AuthMethod | undefined;
  showPasswordField: boolean;
  setShowPasswordField: Dispatch<SetStateAction<boolean>>;
  setClickedMethod: Dispatch<SetStateAction<AuthMethod | undefined>>;
  setLastUsedAuthMethod: Dispatch<SetStateAction<AuthMethod | undefined>>;
}>({
  authMethod: undefined,
  setAuthMethod: () => {},
  clickedMethod: undefined,
  showPasswordField: false,
  setShowPasswordField: () => {},
  setClickedMethod: () => {},
  setLastUsedAuthMethod: () => {},
});

export default function LoginForm({
  methods = [...authMethods],
  next,
}: {
  methods?: AuthMethod[];
  next?: string;
}) {
  const [showPasswordField, setShowPasswordField] = useState(false);
  const [clickedMethod, setClickedMethod] = useState<AuthMethod | undefined>(
    undefined,
  );

  // For now, we'll use localStorage directly instead of a custom hook
  const [lastUsedAuthMethod, setLastUsedAuthMethod] = useState<AuthMethod | undefined>(
    typeof window !== 'undefined' 
      ? (localStorage.getItem('last-used-auth-method') as AuthMethod) || 'email'
      : 'email'
  );

  const [authMethod, setAuthMethod] = useState<AuthMethod | undefined>('email');

  // Reset the state when leaving the page
  useEffect(() => () => setClickedMethod(undefined), []);

  const authProviders: {
    method: AuthMethod;
    component: ComponentType;
    props?: Record<string, unknown>;
  }[] = [
    {
      method: 'email',
      component: EmailSignIn,
      props: { next },
    },
  ];

  const currentAuthProvider = authProviders.find(
    (provider) => provider.method === authMethod,
  );

  const AuthMethodComponent = currentAuthProvider?.component;

  const showEmailPasswordOnly = authMethod === 'email' && showPasswordField;

  return (
    <LoginFormContext.Provider
      value={{
        authMethod,
        setAuthMethod,
        clickedMethod,
        showPasswordField,
        setShowPasswordField,
        setClickedMethod,
        setLastUsedAuthMethod,
      }}
    >
      <div className="flex flex-col gap-3">
        <AnimatedSizeContainer height>
          <div className="flex flex-col gap-3 p-1">
            {authMethod && (
              <div className="flex flex-col gap-3">
                {AuthMethodComponent && (
                  <AuthMethodComponent {...currentAuthProvider?.props} />
                )}

                {!showEmailPasswordOnly &&
                  authMethod === lastUsedAuthMethod && (
                    <div className="text-center text-xs">
                      <span className="text-neutral-500">
                        You signed in with{' '}
                        {lastUsedAuthMethod.charAt(0).toUpperCase() +
                          lastUsedAuthMethod.slice(1)}{' '}
                        last time
                      </span>
                    </div>
                  )}
                <AuthMethodsSeparator />
              </div>
            )}

            {showEmailPasswordOnly && (
              <div className="mt-2">
                <Button
                  variant="secondary"
                  onClick={() => setShowPasswordField(false)}
                  text="Continue with another method"
                />
              </div>
            )}
          </div>
        </AnimatedSizeContainer>
      </div>
    </LoginFormContext.Provider>
  );
}
