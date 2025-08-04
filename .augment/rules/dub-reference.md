---
type: "always_apply"
---

# Dub.co Reference & Component Development

## Backend-First Migration Strategy
**Phase 1**: Laravel backend (API, models, business logic) → **Phase 2**: React components from `/Users/yasinboelhouwer/shorts/dub-main/`

**CRITICAL**: Always reference `dub-main` repository for UI/UX patterns, component structures, and API designs before implementation.

## Dub-Main Component Architecture

### Core Component Patterns (from Analysis)
Based on comprehensive analysis of `/Users/yasinboelhouwer/shorts/dub-main/`:

#### **CardList System** (Primary Pattern)
```typescript
// dub-main/packages/ui/src/card-list/card-list.tsx
const cardListVariants = cva(
  "group/card-list w-full flex flex-col transition-[gap,opacity] min-w-0",
  {
    variants: {
      variant: { compact: "gap-0", loose: "gap-4" },
      loading: { true: "opacity-50" },
    },
  },
);
```

#### **PageWidthWrapper Pattern**
```typescript
// dub-main/apps/web/ui/layout/page-width-wrapper.tsx
<div className="@container/page mx-auto w-full max-w-screen-xl px-3 lg:px-6">
  {children}
</div>
```

#### **Link Card Architecture**
```typescript
// dub-main/apps/web/ui/links/link-card.tsx
export const LinkCardContext = createContext<{
  showTests: boolean;
  setShowTests: Dispatch<SetStateAction<boolean>>;
} | null>(null);

// Two-column layout: Title + Details
<div className="flex items-center gap-5 px-4 py-2.5 text-sm sm:gap-8 md:gap-12">
  <div className="min-w-0 grow"><LinkTitleColumn /></div>
  <LinkDetailsColumn />
</div>
```

### 2. **Component Mapping Documentation**
For each new component, document:

```typescript
/**
 * Component: LinkCard
 *
 * Dub.co Reference: /apps/web/ui/links/link-card.tsx
 *
 * Key Patterns Adopted:
 * - Two-column layout (title + details)
 * - Hover state management with CardContext
 * - Selection mode with checkbox overlay
 * - Responsive gap spacing (gap-5 sm:gap-8 md:gap-12)
 *
 * Adaptations for Laravel + Inertia.js:
 * - Replaced Next.js Link with Inertia Link
 * - Adapted router.push to Inertia.visit
 * - Modified data fetching to use Inertia props
 */
```

## Component Development Process

### 1. **Structure Analysis**
```typescript
// STEP 1: Analyze dub-main component structure
// Example: dub-main/apps/web/ui/links/link-card.tsx

// STEP 2: Identify key patterns
interface DubMainPatterns {
  layout: "two-column" | "single-column" | "grid";
  interactivity: "clickable" | "selectable" | "static";
  responsiveness: "mobile-first" | "desktop-first";
  stateManagement: "context" | "props" | "hooks";
}

// STEP 3: Adapt to our architecture
interface OurImplementation extends DubMainPatterns {
  routing: "inertia"; // Always use Inertia.js routing
  dataFetching: "props"; // Always use Inertia props
  formHandling: "useForm"; // Always use Inertia useForm
}
```

### 2. **Styling Consistency**
```typescript
// REQUIRED: Match dub-main styling patterns exactly

// ✅ CORRECT - Following dub-main patterns
<div className="flex items-center gap-5 px-4 py-2.5 text-sm sm:gap-8 md:gap-12">
  <div className="min-w-0 grow">
    {/* Title column content */}
  </div>
  <div className="flex items-center justify-end gap-2 sm:gap-5">
    {/* Details column content */}
  </div>
</div>

// ❌ INCORRECT - Custom styling without dub-main reference
<div className="flex justify-between p-4">
  {/* This doesn't follow dub-main patterns */}
</div>
```

### 3. **Component Props Interface**
```typescript
// REQUIRED: Follow dub-main prop patterns

// Dub.co pattern analysis:
interface DubLinkCardProps {
  link: ResponseLink; // Complex object with nested data
  // Props are minimal, data is rich
}

// Our adaptation:
interface LinkCardProps {
  link: Link; // Our Laravel model structure
  // Maintain similar prop simplicity
  // Rich data structure in the link object
}

// ✅ CORRECT - Simple props, rich data
export function LinkCard({ link }: LinkCardProps) {
  // Component implementation
}

// ❌ INCORRECT - Too many individual props
export function LinkCard({
  id, title, url, clicks, createdAt, user, tags
}: ComplexProps) {
  // This doesn't follow dub-main patterns
}
```

### 4. **State Management Patterns**
```typescript
// REQUIRED: Follow dub-main state management patterns

// Dub.co pattern: Context for shared state
const LinkCardContext = createContext<{
  showTests: boolean;
  setShowTests: Dispatch<SetStateAction<boolean>>;
} | null>(null);

// Our adaptation: Same pattern
const LinkCardContext = createContext<{
  showDetails: boolean;
  setShowDetails: Dispatch<SetStateAction<boolean>>;
} | null>(null);

// ✅ CORRECT - Context for component-level state
export function LinkCard({ link }: LinkCardProps) {
  const [showDetails, setShowDetails] = useState(false);

  return (
    <LinkCardContext.Provider value={{ showDetails, setShowDetails }}>
      <LinkCardInner link={link} />
    </LinkCardContext.Provider>
  );
}
```

## Required Code Patterns

### 1. **Layout Wrapper Pattern**
```typescript
// REQUIRED: Always use PageWidthWrapper for page-level components
import { PageWidthWrapper } from '@/components/layout/page-width-wrapper';

export default function LinksIndex({ links }: LinksIndexProps) {
  return (
    <AppLayout>
      <Head title="Links" />

      {/* REQUIRED: Follow dub-main PageWidthWrapper pattern */}
      <PageWidthWrapper className="grid gap-y-2">
        {/* Page content */}
      </PageWidthWrapper>
    </AppLayout>
  );
}
```

### 2. **Card List Pattern**
```typescript
// REQUIRED: Use CardList for list displays
import { CardList } from '@/components/ui/card-list';

export function LinksList({ links, loading }: LinksListProps) {
  return (
    <CardList variant="loose" loading={loading}>
      {links?.map(link => (
        <CardList.Card key={link.id} onClick={handleClick}>
          <LinkCard link={link} />
        </CardList.Card>
      ))}
    </CardList>
  );
}
```

### 3. **Empty State Pattern**
```typescript
// REQUIRED: Use AnimatedEmptyState for empty states
import { AnimatedEmptyState } from '@/components/shared/animated-empty-state';

export function EmptyLinksState() {
  return (
    <AnimatedEmptyState
      title="No links yet"
      description="Start creating short links for your campaigns"
      cardContent={<LinkCardPlaceholder />}
      addButton={<CreateLinkButton />}
      learnMoreHref="/docs/creating-links"
    />
  );
}
```

### 4. **Button Variants Pattern**
```typescript
// REQUIRED: Use consistent button variants
import { Button } from '@/components/ui/button';

// ✅ CORRECT - Following dub-main variants
<Button variant="primary" loading={processing}>
  Create Link
</Button>
<Button variant="secondary">
  Cancel
</Button>
<Button variant="danger">
  Delete
</Button>

// ❌ INCORRECT - Custom variants not in dub-main
<Button variant="custom-green">
  Custom Button
</Button>
```

## Quality Assurance Checklist

### **Visual Consistency (MANDATORY)**
- [ ] Component visually matches dub-main equivalent
- [ ] Colors follow dub-main neutral palette + our green theme
- [ ] Typography matches dub-main hierarchy
- [ ] Spacing follows dub-main gap/padding patterns
- [ ] Hover states match dub-main behavior
- [ ] Loading states use dub-main patterns

### **Functional Consistency (MANDATORY)**
- [ ] User interactions match dub-main behavior
- [ ] Form validation follows dub-main patterns
- [ ] Error handling matches dub-main approach
- [ ] Keyboard navigation works like dub-main
- [ ] Screen reader support matches dub-main

### **Code Quality (MANDATORY)**
- [ ] Props interface follows dub-main patterns
- [ ] TypeScript types match dub-main conventions
- [ ] CSS classes follow dub-main naming
- [ ] Component composition matches dub-main
- [ ] Performance optimizations applied

### **Architecture Adaptation (MANDATORY)**
- [ ] Next.js patterns adapted to Inertia.js
- [ ] Router usage converted to Inertia navigation
- [ ] Data fetching uses Inertia props
- [ ] Form handling uses Inertia useForm
- [ ] Authentication adapted to Laravel patterns

## Common Patterns Reference

### **Responsive Design**
```typescript
// REQUIRED: Follow dub-main responsive patterns
className="gap-5 sm:gap-8 md:gap-12" // Progressive enhancement
className="hidden sm:block" // Mobile-first hiding
className="flex-col sm:flex-row" // Layout changes
```

### **Interactive States**
```typescript
// REQUIRED: Follow dub-main hover patterns
className="hover:bg-neutral-50 transition-colors"
className="hover:drop-shadow-card-hover transition-[filter]"
```

### **Loading States**
```typescript
// REQUIRED: Use dub-main loading patterns
<CardList variant="loose" loading={isLoading}>
  {/* Loading placeholder cards */}
</CardList>
```

This workflow ensures every component we create maintains perfect consistency with Dub.co's proven design patterns while adapting seamlessly to our Laravel + React + Inertia.js architecture.