/**
 * AnimatedSizeContainer Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/packages/ui/src/animated-size-container.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Added custom useResizeObserver hook
 * - Maintained exact visual consistency with dub-main
 * - Uses framer-motion for smooth animations
 */

import { cn } from '@/lib/utils';
import { motion } from 'framer-motion';
import {
  ComponentPropsWithoutRef,
  PropsWithChildren,
  forwardRef,
  useRef,
} from 'react';
import { useResizeObserver } from '@/hooks/use-resize-observer';

type AnimatedSizeContainerProps = PropsWithChildren<{
  width?: boolean;
  height?: boolean;
}> &
  Omit<ComponentPropsWithoutRef<typeof motion.div>, 'animate' | 'children'>;

/**
 * A container with animated width and height (each optional) based on children dimensions
 */
const AnimatedSizeContainer = forwardRef<
  HTMLDivElement,
  AnimatedSizeContainerProps
>(
  (
    {
      width = false,
      height = false,
      className,
      transition,
      children,
      ...rest
    }: AnimatedSizeContainerProps,
    forwardedRef,
  ) => {
    const containerRef = useRef<HTMLDivElement>(null);
    const resizeObserverEntry = useResizeObserver(containerRef);

    return (
      <motion.div
        ref={forwardedRef}
        className={cn('overflow-hidden', className)}
        animate={{
          width: width
            ? resizeObserverEntry?.contentRect?.width ?? 'auto'
            : 'auto',
          height: height
            ? resizeObserverEntry?.contentRect?.height ?? 'auto'
            : 'auto',
        }}
        transition={transition ?? { type: 'spring', duration: 0.3 }}
        {...rest}
      >
        <div
          ref={containerRef}
          className={cn(height && 'h-max', width && 'w-max')}
        >
          {children}
        </div>
      </motion.div>
    );
  },
);

AnimatedSizeContainer.displayName = 'AnimatedSizeContainer';

export { AnimatedSizeContainer };
