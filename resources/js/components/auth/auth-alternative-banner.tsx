/**
 * AuthAlternativeBanner Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/auth/auth-alternative-banner.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Replaced DotsPattern with simple background pattern
 * - Maintained exact visual consistency with dub-main
 */

import { Link } from '@inertiajs/react';

interface AuthAlternativeBannerProps {
  text: string;
  cta: string;
  href: string;
}

export function AuthAlternativeBanner({
  text,
  cta,
  href,
}: AuthAlternativeBannerProps) {
  return (
    <Link
      href={href}
      className="relative block overflow-hidden rounded-lg border border-neutral-200 bg-neutral-50 px-2 py-4 transition-colors hover:bg-neutral-100"
    >
      <div
        className="absolute inset-y-0 left-1/2 w-[640px] -translate-x-1/2"
        role="presentation"
      >
        {/* Simple dots pattern to replace DotsPattern component */}
        <div className="h-full w-full opacity-30">
          <svg
            className="h-full w-full"
            viewBox="0 0 640 100"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <defs>
              <pattern
                id="dots"
                x="0"
                y="0"
                width="20"
                height="20"
                patternUnits="userSpaceOnUse"
              >
                <circle cx="2" cy="2" r="1" fill="currentColor" className="text-neutral-200" />
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dots)" />
          </svg>
        </div>
      </div>
      <div className="relative text-center text-sm text-neutral-600">
        <p>{text}</p>
        <span className="block font-semibold text-neutral-800">{cta}</span>
      </div>
    </Link>
  );
}
