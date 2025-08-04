/**
 * RadioGroup Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/packages/ui/src/radio-group.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced @radix-ui/react-radio-group with native HTML radio inputs
 * - Maintained exact visual consistency with dub-main
 * - Will be enhanced with Radix UI integration later
 */

import { cn } from '@/lib/utils';
import { Circle } from 'lucide-react';
import * as React from 'react';

interface RadioGroupProps extends React.HTMLAttributes<HTMLDivElement> {
  value?: string;
  onValueChange?: (value: string) => void;
  name?: string;
}

const RadioGroup = React.forwardRef<HTMLDivElement, RadioGroupProps>(
  ({ className, value, onValueChange, name, children, ...props }, ref) => {
    return (
      <div
        className={cn('grid gap-2', className)}
        ref={ref}
        role="radiogroup"
        {...props}
      >
        {React.Children.map(children, (child) => {
          if (React.isValidElement(child) && child.type === RadioGroupItem) {
            return React.cloneElement(child, {
              name,
              checked: child.props.value === value,
              onChange: () => onValueChange?.(child.props.value),
            });
          }
          return child;
        })}
      </div>
    );
  }
);
RadioGroup.displayName = 'RadioGroup';

interface RadioGroupItemProps extends React.InputHTMLAttributes<HTMLInputElement> {
  value: string;
}

const RadioGroupItem = React.forwardRef<HTMLInputElement, RadioGroupItemProps>(
  ({ className, value, ...props }, ref) => {
    return (
      <div className="relative inline-flex items-center">
        <input
          ref={ref}
          type="radio"
          value={value}
          className={cn(
            'peer aspect-square h-4 w-4 rounded-full border border-neutral-300 bg-white text-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            className,
          )}
          {...props}
        />
        <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
          <Circle className="h-2.5 w-2.5 fill-current text-blue-500 opacity-0 peer-checked:opacity-100 transition-opacity" />
        </div>
      </div>
    );
  }
);
RadioGroupItem.displayName = 'RadioGroupItem';

export { RadioGroup, RadioGroupItem };
