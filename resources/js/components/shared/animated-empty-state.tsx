import React from 'react';
import { Button } from '@/components/ui/button';

interface AnimatedEmptyStateProps {
  title: string;
  description?: string;
  cardContent?: React.ReactNode;
  addButton?: React.ReactNode; // e.g., <Button variant="primary">Create</Button>
  learnMoreHref?: string;
  className?: string;
}

// Adapted from dub-main AnimatedEmptyState pattern
export function AnimatedEmptyState({
  title,
  description,
  cardContent,
  addButton,
  learnMoreHref,
  className,
}: AnimatedEmptyStateProps) {
  return (
    <div className={['text-center py-10', className].filter(Boolean).join(' ')}>
      {cardContent && (
        <div className="mx-auto mb-6 max-w-sm">
          {cardContent}
        </div>
      )}

      <h3 className="text-sm font-medium text-neutral-900">{title}</h3>
      {description && (
        <p className="mt-2 text-sm text-neutral-500">{description}</p>
      )}

      <div className="mt-6 flex items-center justify-center gap-3">
        {addButton}
        {learnMoreHref && (
          <a
            href={learnMoreHref}
            target="_blank"
            rel="noreferrer"
            className="text-sm text-neutral-500 underline underline-offset-4 hover:text-neutral-700"
          >
            Learn more
          </a>
        )}
      </div>
    </div>
  );
}

