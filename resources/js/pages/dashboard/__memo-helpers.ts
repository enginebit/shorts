import React from 'react';

// Simple shallow compare for common card props
export function shallowCompare<T extends object>(prev: T, next: T): boolean {
  const prevKeys = Object.keys(prev) as (keyof T)[];
  const nextKeys = Object.keys(next) as (keyof T)[];
  if (prevKeys.length !== nextKeys.length) return false;
  for (const k of prevKeys) {
    if (prev[k] !== next[k]) return false;
  }
  return true;
}

export function memoWithShallow<T extends React.ComponentType<any>>(component: T) {
  return React.memo(component, shallowCompare);
}

