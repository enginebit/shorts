/**
 * AuthLayout Component
 *
 * Dub.co Reference: /apps/web/ui/layout/auth-layout.tsx
 *
 * Key Patterns Adopted:
 * - ClientOnly wrapper for hydration safety
 * - Suspense boundary for async components
 * - Three-section layout (spacer, content, footer)
 * - Conditional terms display
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses our ClientOnly component implementation
 * - Maintains exact visual consistency with dub-main
 * - Updated legal links to our routes
 * - Proper TypeScript interfaces
 */

import { ReactNode, Suspense } from 'react';
import { ClientOnly } from '@/components/ui';
import { ProtocolPage } from '@/components/shared/protocol-background';
import ThemeToggle from '@/components/shared/theme-toggle';

interface AuthLayoutProps {
  children: ReactNode;
  showTerms?: boolean;
}

export default function AuthLayout({ children, showTerms = false }: AuthLayoutProps) {
  return (
    <ProtocolPage variant="full">
      <div className="flex min-h-screen w-full flex-col items-center justify-between">
        {/* Top bar with theme toggle */}
        <div className="flex w-full items-center justify-end px-4 py-4">
          <ThemeToggle />
        </div>

        <ClientOnly className="relative flex w-full flex-col items-center justify-center px-4">
          <Suspense>{children}</Suspense>
        </ClientOnly>

        <div className="flex grow basis-0 flex-col justify-end">
          {showTerms && (
            <p className="px-20 py-8 text-center text-xs font-medium text-[rgb(var(--content-subtle))] md:px-0">
              By continuing, you agree to brachy.io&rsquo;s{" "}
              <a
                href="/legal/terms"
                target="_blank"
                className="font-semibold text-[rgb(var(--content-default))] hover:text-[rgb(var(--content-emphasis))]"
              >
                Terms of Service
              </a>{" "}
              and{" "}
              <a
                href="/legal/privacy"
                target="_blank"
                className="font-semibold text-[rgb(var(--content-default))] hover:text-[rgb(var(--content-emphasis))]"
              >
                Privacy Policy
              </a>
            </p>
          )}
        </div>
      </div>
    </ProtocolPage>
  );
}
