/**
 * Register Context
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/auth/register/context.tsx
 *
 * Key Patterns Adopted:
 * - Two-step registration flow (signup → verify)
 * - State management for email, password, and current step
 * - Context provider pattern for sharing state across components
 *
 * Adaptations for Laravel + Inertia.js:
 * - Maintains exact same interface as dub-main
 * - Compatible with our Laravel backend OTP system
 * - Uses TypeScript for type safety
 */

import React, {
  createContext,
  PropsWithChildren,
  useContext,
  useState,
} from 'react';

interface RegisterContextType {
  email: string;
  password: string;
  step: 'signup' | 'verify';
  setEmail: (email: string) => void;
  setPassword: (password: string) => void;
  setStep: (step: 'signup' | 'verify') => void;
  lockEmail?: boolean;
}

const RegisterContext = createContext<RegisterContextType | undefined>(
  undefined,
);

export const RegisterProvider: React.FC<
  PropsWithChildren<{ email?: string; lockEmail?: boolean }>
> = ({ email: emailProp, lockEmail, children }) => {
  const [email, setEmail] = useState(emailProp ?? '');
  const [password, setPassword] = useState('');
  const [step, setStep] = useState<'signup' | 'verify'>('signup');

  return (
    <RegisterContext.Provider
      value={{
        email,
        password,
        step,
        setEmail,
        setPassword,
        setStep,
        lockEmail,
      }}
    >
      {children}
    </RegisterContext.Provider>
  );
};

export const useRegisterContext = () => {
  const context = useContext(RegisterContext);

  if (context === undefined) {
    throw new Error(
      'useRegisterContext must be used within a RegisterProvider',
    );
  }

  return context;
};
