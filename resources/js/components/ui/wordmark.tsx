/**
 * Wordmark Component
 *
 * Based on dub-main's Wordmark component
 * Simple text-based logo for the application
 */

import { cn } from '@/lib/utils';

interface WordmarkProps {
  className?: string;
}

export function Wordmark({ className }: WordmarkProps) {
  return (
    <div className={cn("font-bold text-[rgb(var(--content-emphasis))]", className)}>
      brachy.io
    </div>
  );
}
