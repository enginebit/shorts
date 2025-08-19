/**
 * Protocol Background Component
 *
 * Migrated from: /Users/yasinboelhouwer/Downloads/tailwind-plus-protocol/protocol-ts/src/components/HeroPattern.tsx
 *
 * Creates the sophisticated Protocol design background with:
 * - Teal to lime green gradient
 * - Grid pattern overlay
 * - Blur and masking effects
 * - Responsive light/dark mode support
 */

import { GridPattern } from './grid-pattern';

interface ProtocolBackgroundProps {
  className?: string;
  variant?: 'full' | 'subtle';
}

export function ProtocolBackground({
  className = '',
  variant = 'full'
}: ProtocolBackgroundProps) {
  return (
    <div className={`absolute inset-0 z-0 mx-0 max-w-none overflow-hidden ${className}`}>
      <div className="absolute top-0 left-1/2 -ml-[38rem] h-[35rem] w-[81.25rem] pointer-events-none dark:protocol-mask-linear">
        <div
          className={`absolute inset-0 protocol-gradient protocol-mask-radial ${
            variant === 'full'
              ? 'opacity-60 dark:opacity-100'
              : 'opacity-20 dark:opacity-50'
          }`}
        >
          <GridPattern
            width={72}
            height={56}
            x={-12}
            y={4}
            squares={[
              [4, 3],
              [2, 1],
              [7, 3],
              [10, 6],
            ]}
            className="absolute inset-x-0 inset-y-[-50%] h-[200%] w-full skew-y-[-18deg] fill-[rgb(var(--content-emphasis))]/40 stroke-[rgb(var(--content-emphasis))]/50 mix-blend-overlay dark:fill-[rgb(var(--content-inverted))]/10 dark:stroke-[rgb(var(--content-inverted))]/20"
          />
        </div>
        <svg
          viewBox="0 0 1113 440"
          aria-hidden="true"
          className="absolute top-0 left-1/2 -ml-[19rem] w-[69.5625rem] fill-white blur-[26px] dark:hidden"
        >
          <path d="M.016 439.5s-9.5-300 434-300S882.516 20 882.516 20V0h230.004v439.5H.016Z" />
        </svg>
      </div>
    </div>
  );
}

/**
 * Protocol Page Wrapper
 *
 * Wraps pages that should use the Protocol background system
 */
interface ProtocolPageProps {
  children: React.ReactNode;
  variant?: 'full' | 'subtle';
  className?: string;
}

export function ProtocolPage({
  children,
  variant = 'full',
  className = ''
}: ProtocolPageProps) {
  return (
    <div className={`relative isolate min-h-screen bg-[rgb(var(--bg-default))] ${className}`}>
      <ProtocolBackground variant={variant} />
      <div className="relative z-10">
        {children}
      </div>
    </div>
  );
}
