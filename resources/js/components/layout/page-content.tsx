/**
 * PageContent Component
 *
 * Dub.co Reference: /apps/web/ui/layout/page-content/index.tsx
 *
 * Key Patterns Adopted:
 * - Flexible header with title, back button, and controls
 * - InfoTooltip integration for contextual help
 * - Responsive height adjustments (h-12 sm:h-16)
 * - PageWidthWrapper integration for consistent layout
 * - Conditional header rendering based on content
 *
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Uses our PageWidthWrapper component
 * - Simplified InfoTooltip implementation for now
 * - Maintained exact visual consistency with dub-main
 * - Added proper TypeScript interfaces
 */

import { cn } from '@/lib/utils';
import { ChevronLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { PropsWithChildren, ReactNode } from 'react';
import { PageWidthWrapper } from './page-width-wrapper';
import { NavButton } from '@/components/navigation/nav-button';
import { Tooltip } from '@/components/ui';

export interface PageContentProps {
  title?: ReactNode;
  titleInfo?: ReactNode | { title: string; href?: string };
  titleBackHref?: string;
  controls?: ReactNode;
  className?: string;
  contentWrapperClassName?: string;
}

export function PageContent({
  title,
  titleInfo,
  titleBackHref,
  controls,
  className,
  contentWrapperClassName,
  children,
}: PropsWithChildren<PageContentProps>) {
  // Generate titleInfo from object if provided
  const finalTitleInfo =
    titleInfo && typeof titleInfo === 'object' && 'title' in titleInfo ? (
      <Tooltip
        content={
          <div className="max-w-xs">
            <div className="font-medium">{titleInfo.title}</div>
            {titleInfo.href && (
              <Link
                href={titleInfo.href}
                className="text-xs text-neutral-400 hover:text-neutral-300"
              >
                Learn more
              </Link>
            )}
          </div>
        }
      >
        <div className="flex size-4 items-center justify-center rounded-full bg-neutral-200 text-xs text-neutral-600">
          ?
        </div>
      </Tooltip>
    ) : (
      titleInfo
    );

  const hasHeaderContent = !!(title || controls);

  return (
    <div
      className={cn(
        'rounded-t-[inherit] bg-neutral-100 md:bg-white',
        className,
      )}
    >
      <div
        className={cn('border-neutral-200', hasHeaderContent && 'border-b')}
      >
        <PageWidthWrapper>
          <div
            className={cn(
              'flex h-12 items-center justify-between gap-4',
              hasHeaderContent ? 'sm:h-16' : 'sm:h-0',
            )}
          >
            <div className="flex min-w-0 items-center gap-4">
              <NavButton />
              {title && (
                <div className="flex items-center gap-2">
                  {titleBackHref && (
                    <Link
                      href={titleBackHref}
                      className="rounded-lg p-1.5 text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-900"
                    >
                      <ChevronLeft className="size-5" />
                    </Link>
                  )}
                  <h1 className="text-neutral-900 text-lg font-semibold leading-7">
                    {title}
                  </h1>
                  {finalTitleInfo}
                </div>
              )}
            </div>
            {controls && (
              <div className="flex items-center gap-2">{controls}</div>
            )}
          </div>
        </PageWidthWrapper>
      </div>
      <div
        className={cn(
          'rounded-t-[inherit] bg-white pt-3 lg:pt-6',
          contentWrapperClassName,
        )}
      >
        {children}
      </div>
    </div>
  );
}
