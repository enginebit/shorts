import * as React from "react";

// Minimal DropdownMenu primitives to satisfy build and basic usage
// Render-only; no positioning logic. For production, replace with radix or shadcn if needed.

export function DropdownMenu({ children }: { children: React.ReactNode }) {
  return (
    <div data-slot="dropdown-menu" role="menu" aria-orientation="vertical">
      {children}
    </div>
  );
}

export function DropdownMenuTrigger({ children, className, ...props }: React.ComponentProps<"button">) {
  return (
    <button
      data-slot="dropdown-menu-trigger"
      className={className}
      aria-haspopup="menu"
      aria-expanded={props['aria-expanded']}
      {...props}
    >
      {children}
    </button>
  );
}

export function DropdownMenuContent({ children, className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="dropdown-menu-content"
      className={className}
      role="menu"
      tabIndex={-1}
      {...props}
    >
      {children}
    </div>
  );
}

export function DropdownMenuItem({ children, className, ...props }: React.ComponentProps<"div">) {
  return (
    <div data-slot="dropdown-menu-item" role="menuitem" className={className} {...props}>
      {children}
    </div>
  );
}

export function DropdownMenuLabel({ children, className, ...props }: React.ComponentProps<"div">) {
  return (
    <div data-slot="dropdown-menu-label" className={className} {...props}>
      {children}
    </div>
  );
}

export function DropdownMenuSeparator({ className, ...props }: React.ComponentProps<"div">) {
  return <div data-slot="dropdown-menu-separator" role="separator" className={className} {...props} />;
}

