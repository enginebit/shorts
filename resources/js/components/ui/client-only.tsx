/**
 * ClientOnly Component
 *
 * Dub.co Reference: /packages/ui/src/client-only.tsx
 *
 * Key Patterns Adopted:
 * - Prevents hydration mismatches by only rendering on client
 * - Supports className passthrough for styling
 * - Fallback content during SSR/initial render
 * - TypeScript support with proper prop types
 *
 * Adaptations for Laravel + Inertia.js:
 * - Optimized for client-side rendering (Inertia.js doesn't use SSR by default)
 * - Maintains exact dub-main API and behavior
 * - Compatible with our component architecture
 */

import { ReactNode, useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

interface ClientOnlyProps {
  children: ReactNode;
  className?: string;
  fallback?: ReactNode;
}

export function ClientOnly({ 
  children, 
  className, 
  fallback = null 
}: ClientOnlyProps) {
  const [hasMounted, setHasMounted] = useState(false);

  useEffect(() => {
    setHasMounted(true);
  }, []);

  if (!hasMounted) {
    return fallback ? (
      <div className={className}>{fallback}</div>
    ) : null;
  }

  return (
    <div className={cn(className)}>
      {children}
    </div>
  );
}
