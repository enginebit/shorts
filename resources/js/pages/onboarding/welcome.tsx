/**
 * Onboarding Welcome Page
 *
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/app/app.dub.co/(onboarding)/onboarding/welcome/page.tsx
 *
 * Key Patterns Adopted:
 * - Animated Wordmark with gradient effects
 * - Slide-up fade animations with staggered delays
 * - NewBackground component with gradient animation
 * - Centered layout with responsive design
 * - "Get started" button leading to workspace creation
 *
 * Adaptations for Laravel + Inertia.js:
 * - Uses Inertia.js navigation instead of Next.js router
 * - Integrates with our Laravel onboarding system
 * - Maintains exact visual consistency with dub-main
 */

import { Head, Link } from '@inertiajs/react';
import AuthLayout from '@/layouts/auth-layout';
import Logo from '@/components/shared/logo';
import { Button } from '@/components/ui';
import { route } from 'ziggy-js';
import { cn } from '@/lib/utils';

export default function Welcome() {
  return (
    <AuthLayout>
      <Head title="Welcome to brachy.io" />

      <div className="relative flex min-h-[70vh] flex-col items-center justify-center">
        <div className="flex max-w-sm flex-col items-center px-4 py-12 text-center">
          <div className="animate-slide-up-fade relative flex w-auto items-center justify-center px-6 py-2 [--offset:20px] [animation-duration:1.3s] [animation-fill-mode:both]">
            <Gradient className="opacity-10 mix-blend-overlay" />
            <Logo variant="full" size="lg" />
            <Gradient className="opacity-40 mix-blend-hard-light" />
          </div>

          <h1 className="animate-slide-up-fade mt-12 text-xl font-semibold text-[rgb(var(--content-emphasis))] [--offset:10px] [animation-delay:250ms] [animation-duration:1s] [animation-fill-mode:both]">
            Welcome to brachy.io
          </h1>

          <p className="animate-slide-up-fade mt-2 text-balance text-base text-[rgb(var(--content-subtle))] [--offset:10px] [animation-delay:500ms] [animation-duration:1s] [animation-fill-mode:both]">
            Get started by creating your first workspace.
          </p>

          <div className="animate-slide-up-fade mt-8 w-full [--offset:10px] [animation-delay:750ms] [animation-duration:1s] [animation-fill-mode:both]">
            <Link href={route('onboarding.workspace.form')} className="w-full">
              <Button text="Get started" className="w-full" />
            </Link>
          </div>
        </div>
      </div>
    </AuthLayout>
  );
}

function Gradient({ className }: { className?: string }) {
  // Removed conic-gradient background per request
  return null;
}
