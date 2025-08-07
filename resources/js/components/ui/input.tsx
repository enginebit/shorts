/**
 * Input Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/packages/ui/src/input.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced @dub/utils with local utils
 * - Replaced custom Eye icons with Lucide React icons
 * - Maintained exact visual consistency with dub-main
 */

import { cn } from '@/lib/utils';
import { AlertCircle, Eye, EyeOff } from 'lucide-react';
import React, { useCallback, useState } from 'react';

export interface InputProps
  extends React.InputHTMLAttributes<HTMLInputElement> {
  error?: string;
}

const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ className, type, ...props }, ref) => {
    const [isPasswordVisible, setIsPasswordVisible] = useState(false);

    const toggleIsPasswordVisible = useCallback(
      () => setIsPasswordVisible(!isPasswordVisible),
      [isPasswordVisible, setIsPasswordVisible],
    );

    return (
      <div>
        <div className="relative flex">
          <input
            type={isPasswordVisible ? 'text' : type}
            className={cn(
              'block w-full min-w-0 appearance-none rounded-md border border-neutral-300 px-3 py-2 placeholder-neutral-400 shadow-sm focus:border-black focus:outline-none focus:ring-1 focus:ring-black sm:text-sm bg-white text-neutral-900',
              props.error &&
                'border-red-500 focus:border-red-500 focus:ring-red-500',
              className,
            )}
            ref={ref}
            {...props}
          />

          <div className="group">
            {props.error && (
              <div className="pointer-events-none absolute inset-y-0 right-0 flex flex-none items-center px-2.5">
                <AlertCircle
                  className={cn(
                    'size-5 text-white',
                    type === 'password' &&
                      'transition-opacity group-hover:opacity-0',
                  )}
                  fill="#ef4444"
                />
              </div>
            )}
            {type === 'password' && (
              <button
                className={cn(
                  'absolute inset-y-0 right-0 flex items-center px-3',
                  props.error &&
                    'opacity-0 transition-opacity group-hover:opacity-100',
                )}
                type="button"
                onClick={() => toggleIsPasswordVisible()}
                aria-label={
                  isPasswordVisible ? 'Hide password' : 'Show Password'
                }
              >
                {isPasswordVisible ? (
                  <Eye
                    className="size-4 flex-none text-neutral-500 transition hover:text-neutral-700"
                    aria-hidden
                  />
                ) : (
                  <EyeOff
                    className="size-4 flex-none text-neutral-500 transition hover:text-neutral-700"
                    aria-hidden
                  />
                )}
              </button>
            )}
          </div>
        </div>

        {props.error && (
          <span
            className="mt-2 block text-sm text-red-500"
            role="alert"
            aria-live="assertive"
          >
            {props.error}
          </span>
        )}
      </div>
    );
  },
);

Input.displayName = 'Input';

export { Input };
