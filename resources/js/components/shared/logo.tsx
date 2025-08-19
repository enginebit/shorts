import React from 'react';
import { cn } from '@/lib/utils';

interface LogoProps {
  variant?: 'full' | 'icon' | 'text';
  className?: string;
  size?: 'sm' | 'md' | 'lg';
  // Optional explicit heights if needed
  iconClassName?: string;
  textClassName?: string;
}

/**
 * Brand Logo component for brachy.io
 * Uses existing SVG assets:
 * - /images/brachy_icon_only.svg
 * - /images/brachy_text_only.svg
 */
export default function Logo({ variant = 'full', className, size = 'md', iconClassName, textClassName }: LogoProps) {
  const sizes = {
    sm: { icon: 'h-6 w-6', text: 'h-5' },
    md: { icon: 'h-8 w-8', text: 'h-6' },
    lg: { icon: 'h-12 w-12', text: 'h-8' },
  }[size];

  if (variant === 'icon') {
    return (
      <img
        src="/images/brachy_icon_only.svg"
        alt="brachy.io"
        className={cn(sizes.icon, iconClassName, className)}
      />
    );
  }

  if (variant === 'text') {
    return (
      <img
        src="/images/brachy_text_only.svg"
        alt="brachy.io"
        className={cn(sizes.text, textClassName, className)}
      />
    );
  }

  // full
  return (
    <div className={cn('flex items-center gap-3', className)} aria-label="brachy.io">
      <img src="/images/brachy_icon_only.svg" alt="brachy.io" className={cn(sizes.icon, iconClassName)} />
      <img src="/images/brachy_text_only.svg" alt="brachy.io" className={cn(sizes.text, textClassName)} />
    </div>
  );
}

