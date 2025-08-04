/**
 * Select Component (Simplified)
 * 
 * Based on: /Users/yasinboelhouwer/shorts/dub-main/packages/ui/src/input-select.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Simplified implementation using native HTML select for now
 * - Maintained visual consistency with dub-main styling
 * - Will be enhanced with full floating UI integration later
 */

import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-react';
import { forwardRef } from 'react';

export interface SelectOption {
  value: string;
  label: string;
  disabled?: boolean;
}

interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  options: SelectOption[];
  placeholder?: string;
  error?: string;
}

const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ className, options, placeholder, error, ...props }, ref) => {
    return (
      <div className="relative">
        <select
          ref={ref}
          className={cn(
            'w-full max-w-md rounded-md border border-neutral-300 bg-white px-3 py-2 pr-10 text-sm text-neutral-900 placeholder-neutral-400 focus:border-neutral-500 focus:outline-none focus:ring-neutral-500 disabled:cursor-not-allowed disabled:opacity-50',
            error && 'border-red-500 focus:border-red-500 focus:ring-red-500',
            className,
          )}
          {...props}
        >
          {placeholder && (
            <option value="" disabled>
              {placeholder}
            </option>
          )}
          {options.map((option) => (
            <option
              key={option.value}
              value={option.value}
              disabled={option.disabled}
            >
              {option.label}
            </option>
          ))}
        </select>
        
        {/* Chevron Icon */}
        <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3">
          <ChevronDown className="size-4 text-neutral-400" />
        </div>
        
        {/* Error Message */}
        {error && (
          <span
            className="mt-2 block text-sm text-red-500"
            role="alert"
            aria-live="assertive"
          >
            {error}
          </span>
        )}
      </div>
    );
  }
);
Select.displayName = 'Select';

export { Select };
