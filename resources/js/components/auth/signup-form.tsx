/**
 * SignUpForm Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/auth/register/signup-form.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Simplified to email/password registration for initial implementation
 * - Replaced Next.js patterns with Inertia.js form handling
 * - Maintained exact visual consistency with dub-main
 * - Uses Laravel validation instead of client-side validation
 */

import { AnimatedSizeContainer } from '@/components/ui';
import { AuthMethodsSeparator } from './auth-methods-separator';
import { SignUpEmail } from './signup-email';

export const SignUpForm = ({
  methods = ['email'],
}: {
  methods?: ('email' | 'google' | 'github')[];
}) => {
  return (
    <AnimatedSizeContainer height>
      <div className="flex flex-col gap-3 p-1">
        {methods.includes('email') && <SignUpEmail />}
        {methods.length > 1 && <AuthMethodsSeparator />}
        {/* OAuth components would go here in future implementation */}
      </div>
    </AnimatedSizeContainer>
  );
};
