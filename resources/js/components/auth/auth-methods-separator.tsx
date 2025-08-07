/**
 * AuthMethodsSeparator Component
 * 
 * Migrated from: /Users/yasinboelhouwer/shorts/dub-main/apps/web/ui/auth/auth-methods-separator.tsx
 * 
 * Adaptations for Laravel + Inertia.js:
 * - Replaced text-content-muted with equivalent neutral color
 * - Maintained exact visual consistency with dub-main
 */

export function AuthMethodsSeparator() {
  return (
    <div className="my-3 flex flex-shrink items-center justify-center gap-2">
      <div className="grow basis-0 border-b border-neutral-200" />
      <span className="text-neutral-500 text-xs font-medium uppercase leading-none">
        or
      </span>
      <div className="grow basis-0 border-b border-neutral-200" />
    </div>
  );
}
