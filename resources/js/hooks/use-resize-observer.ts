/**
 * useResizeObserver Hook
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/packages/ui/src/hooks
 * 
 * Custom hook to observe element size changes using ResizeObserver API
 */

import { RefObject, useEffect, useState } from 'react';

export function useResizeObserver(ref: RefObject<HTMLElement>) {
  const [resizeObserverEntry, setResizeObserverEntry] = useState<ResizeObserverEntry | null>(null);

  useEffect(() => {
    if (!ref.current) return;

    const resizeObserver = new ResizeObserver((entries) => {
      if (entries[0]) {
        setResizeObserverEntry(entries[0]);
      }
    });

    resizeObserver.observe(ref.current);

    return () => {
      resizeObserver.disconnect();
    };
  }, [ref]);

  return resizeObserverEntry;
}
