/**
 * useMediaQuery Hook
 *
 * Dub.co Reference: /packages/ui/src/hooks/use-media-query.ts
 *
 * Key Patterns Adopted:
 * - Responsive breakpoint detection
 * - SSR-safe implementation
 * - Efficient event listener management
 * - TypeScript support with proper return types
 *
 * Adaptations for Laravel + Inertia.js:
 * - Maintains exact dub-main API and behavior
 * - Compatible with our Tailwind v4 breakpoints
 * - Optimized for client-side rendering
 */

import { useEffect, useState } from 'react';

interface MediaQueryOptions {
  defaultValue?: boolean;
  initializeWithValue?: boolean;
}

export function useMediaQuery(
  query: string,
  options: MediaQueryOptions = {}
): boolean {
  const { defaultValue = false, initializeWithValue = true } = options;

  const getMatches = (query: string): boolean => {
    if (typeof window !== 'undefined') {
      return window.matchMedia(query).matches;
    }
    return defaultValue;
  };

  const [matches, setMatches] = useState<boolean>(() => {
    if (initializeWithValue) {
      return getMatches(query);
    }
    return defaultValue;
  });

  useEffect(() => {
    const matchMedia = window.matchMedia(query);

    // Triggered at the first client-side load and if query changes
    const handleChange = () => {
      setMatches(matchMedia.matches);
    };

    // Listen matchMedia
    if (matchMedia.addListener) {
      matchMedia.addListener(handleChange);
    } else {
      matchMedia.addEventListener('change', handleChange);
    }

    return () => {
      if (matchMedia.removeListener) {
        matchMedia.removeListener(handleChange);
      } else {
        matchMedia.removeEventListener('change', handleChange);
      }
    };
  }, [query]);

  return matches;
}

// Convenience hook for common breakpoints matching dub-main patterns
export function useBreakpoint() {
  const isMobile = useMediaQuery('(max-width: 767px)');
  const isTablet = useMediaQuery('(min-width: 768px) and (max-width: 1023px)');
  const isDesktop = useMediaQuery('(min-width: 1024px)');
  const isLarge = useMediaQuery('(min-width: 1280px)');

  return {
    isMobile,
    isTablet,
    isDesktop,
    isLarge,
  };
}
