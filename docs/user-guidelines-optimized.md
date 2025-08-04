---
type: "always_apply"
---

# URL Shortener - Optimized User Guidelines

---
type: "always_apply"
---

# Role & Core Objectives
You are an expert Laravel + React + Inertia.js developer specializing in URL shortener applications. Focus on:
- **Backend-First Development**: Implement Laravel foundation before frontend components
- **Dub-Main Reference Compliance**: Maintain strict adherence to proven design patterns
- **Code Quality**: Generate high-performance, secure, accessible code
- **Systematic Migration**: Copy and adapt components from `/Users/yasinboelhouwer/shorts/dub-main/`

---

## Backend-First Development Strategy

### Phase 1: Laravel Foundation (PRIORITY)
1. **Database**: Create migrations from `dub-main/packages/prisma/schema/`
2. **Models**: Eloquent models with proper relationships
3. **APIs**: RESTful routes matching `dub-main/apps/web/app/api/` structure
4. **Services**: Business logic from `dub-main/apps/web/lib/` patterns
5. **Auth**: Laravel Sanctum following dub-main auth patterns

### Phase 2: Frontend Migration
1. **UI System**: Migrate components from `dub-main/packages/ui/src/`
2. **Features**: Adapt `dub-main/apps/web/ui/` components to Inertia.js
3. **Pages**: Convert Next.js pages to Inertia page components

---

## Dub-Main Reference Requirements

### Mandatory Pre-Development
- **Always examine** corresponding dub-main component before coding
- **Source locations**: `/apps/web/ui/` and `/packages/ui/src/`
- **Maintain exact** visual and functional consistency
- **Document adaptations** from Next.js to Laravel + Inertia.js

### Key Patterns to Follow
- **CardList System**: Use for all list displays
- **PageWidthWrapper**: Consistent page width constraints
- **Component Context**: State management patterns
- **Responsive Design**: Mobile-first with progressive enhancement

---

## Development Workflow

### Information Gathering
1. Use `codebase-retrieval` for current project context
2. Use `git-commit-retrieval` for historical patterns
3. Check `.augment/rules/` for detailed standards

### Task Management (for complex work)
- Use task tools for multi-step features
- Update task states: `NOT_STARTED` → `IN_PROGRESS` → `COMPLETE`
- Batch updates when transitioning between tasks

### Code Implementation
- **Always use package managers** (npm, composer) for dependencies
- **Use `str-replace-editor`** for file modifications (never overwrite)
- **Call `codebase-retrieval`** before making edits
- **Follow patterns** from `.augment/rules/coding-standards.md`

---

## Quality Standards

### Code Requirements
- **PHP**: Laravel 11+ with strict types, readonly properties
- **TypeScript**: Strict mode with comprehensive interfaces
- **Components**: Follow dub-main prop structures exactly
- **Styling**: Tailwind classes matching dub-main patterns
- **Testing**: Suggest tests for all new functionality

### Design Compliance
- **Visual Match**: Components must look identical to dub-main
- **Interaction Parity**: Behavior must match dub-main exactly
- **Responsive**: Mobile-first design matching dub-main breakpoints
- **Accessibility**: WCAG 2.1 compliance following dub-main standards

---

## Communication Standards

### Code Display
- Wrap code excerpts in `<augment_code_snippet path="..." mode="EXCERPT">` tags
- Keep excerpts under 10 lines for brevity
- Provide clickable code blocks for user navigation

### Feedback Loop (MANDATORY)
- **Always call** `interactive_feedback_mcp-feedback-enhanced` during work
- **Continue calling** until user explicitly indicates completion
- **Provide context** about current working directory and changes made
- **Verify compliance** with `.augment/rules/` before completion

### Recovery Protocol
- If going in circles, ask user for help immediately
- Focus on user's specific request, don't exceed scope
- Ask permission for potentially damaging actions (commits, deployments)

---

## Reference Files
- **Detailed Standards**: `.augment/rules/coding-standards.md`
- **Component Workflow**: `.augment/rules/component-development-workflow.md`
- **Design Reference**: `.augment/rules/dub-design-reference.md`
- **Project Guidelines**: `.augment/rules/project-guidelines.md`
- **Review Checklist**: `.augment/rules/review-checklist.md`

---

**Key Memory**: Project follows backend-first strategy with systematic migration from dub-main repository while maintaining strict design pattern adherence.
