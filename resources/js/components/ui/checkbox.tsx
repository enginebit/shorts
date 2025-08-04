/**
 * Checkbox Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/packages/ui/src/checkbox.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced @radix-ui/react-checkbox with native HTML checkbox for now
 * - Replaced custom icons with Lucide React icons
 * - Maintained exact visual consistency with dub-main
 * - Will be enhanced with Radix UI integration later
 */

import { cn } from '@/lib/utils';
import { Check, Minus } from 'lucide-react';
import { forwardRef } from 'react';

interface CheckboxProps extends React.InputHTMLAttributes<HTMLInputElement> {
  indeterminate?: boolean;
}

const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(
  ({ className, indeterminate, ...props }, ref) => {
    return (
      <div className="relative inline-flex items-center">
        <input
          type="checkbox"
          ref={ref}
          className={cn(
            'peer h-5 w-5 shrink-0 rounded-md border border-neutral-200 bg-white outline-none focus-visible:border-black disabled:cursor-not-allowed disabled:opacity-50 checked:bg-blue-500 checked:border-blue-500',
            className,
          )}
          {...props}
        />
        <div className="pointer-events-none absolute inset-0 flex items-center justify-center text-white">
          {indeterminate ? (
            <Minus className="size-3" />
          ) : (
            <Check className="size-3 opacity-0 peer-checked:opacity-100 transition-opacity" />
          )}
        </div>
      </div>
    );
  }
);
Checkbox.displayName = 'Checkbox';

export { Checkbox };
