/**
 * Page Header Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/layout/page-content/index.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Integrated with our PageWidthWrapper component
 * - Maintained exact visual consistency and layout
 * - Added proper TypeScript interfaces
 * - Integrated with our navigation context
 */

import { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeft, Info } from 'lucide-react';
import { cn } from '@/lib/utils';
import { PageWidthWrapper } from '@/components/ui';
import { NavButton } from './nav-button';

interface PageHeaderProps {
  title?: ReactNode;
  titleInfo?: ReactNode | { title: string; href?: string };
  titleBackHref?: string;
  controls?: ReactNode;
  className?: string;
  contentWrapperClassName?: string;
  children?: ReactNode;
}

export function PageHeader({
  title,
  titleInfo,
  titleBackHref,
  controls,
  className,
  contentWrapperClassName,
  children,
}: PageHeaderProps) {
  // Generate titleInfo from object if provided
  const finalTitleInfo =
    titleInfo && typeof titleInfo === "object" && "title" in titleInfo ? (
      <div className="group relative">
        <Info className="size-4 text-gray-400 hover:text-gray-600 cursor-help" />
        <div className="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap">
          {titleInfo.title}
          {titleInfo.href && (
            <Link
              href={titleInfo.href}
              className="ml-2 underline hover:no-underline"
            >
              Learn more
            </Link>
          )}
        </div>
      </div>
    ) : (
      titleInfo
    );

  const hasHeaderContent = !!(title || controls);

  return (
    <div
      className={cn(
        "rounded-t-[inherit] bg-gray-100 md:bg-white",
        className,
      )}
    >
      <div
        className={cn("border-gray-200", hasHeaderContent && "border-b")}
      >
        <PageWidthWrapper>
          <div
            className={cn(
              "flex h-12 items-center justify-between gap-4",
              hasHeaderContent ? "sm:h-16" : "sm:h-0",
            )}
          >
            <div className="flex min-w-0 items-center gap-4">
              <NavButton />
              {title && (
                <div className="flex items-center gap-2">
                  {titleBackHref && (
                    <Link
                      href={titleBackHref}
                      className="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-900"
                    >
                      <ChevronLeft className="size-5" />
                    </Link>
                  )}
                  <h1 className="text-gray-900 text-lg font-semibold leading-7">
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
      {children && (
        <div
          className={cn(
            "rounded-t-[inherit] bg-white pt-3 lg:pt-6",
            contentWrapperClassName,
          )}
        >
          {children}
        </div>
      )}
    </div>
  );
}
