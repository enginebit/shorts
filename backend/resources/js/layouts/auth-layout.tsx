/**
 * AuthLayout Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/layout/auth-layout.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Removed ClientOnly wrapper (not needed in our setup)
 * - Maintained exact visual consistency with dub-main
 * - Added proper TypeScript interfaces
 */

import { ReactNode } from 'react';

interface AuthLayoutProps {
  children: ReactNode;
  showTerms?: boolean;
}

export default function AuthLayout({ children, showTerms = false }: AuthLayoutProps) {
  return (
    <div className="flex min-h-screen w-full flex-col items-center justify-between">
      {/* Empty div to help center main content */}
      <div className="grow basis-0">
        <div className="h-24" />
      </div>

      <div className="relative flex w-full flex-col items-center justify-center px-4">
        {children}
      </div>

      <div className="flex grow basis-0 flex-col justify-end">
        {showTerms && (
          <p className="px-20 py-8 text-center text-xs font-medium text-gray-500 md:px-0">
            By continuing, you agree to Shorts&rsquo;s{" "}
            <a
              href="/legal/terms"
              target="_blank"
              className="font-semibold text-gray-600 hover:text-gray-800"
            >
              Terms of Service
            </a>{" "}
            and{" "}
            <a
              href="/legal/privacy"
              target="_blank"
              className="font-semibold text-gray-600 hover:text-gray-800"
            >
              Privacy Policy
            </a>
          </p>
        )}
      </div>
    </div>
  );
}
