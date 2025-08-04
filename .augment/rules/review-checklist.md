---
type: "always_apply"
---

# Code Review Checklist - Backend-First Laravel + React + Inertia.js

## Backend-First Development Strategy
**Phase 1**: Laravel backend (API, models, business logic) → **Phase 2**: React components from `/Users/yasinboelhouwer/shorts/dub-main/`

## Development Phase Verification

### Phase 1: Backend-First Implementation Review
- [ ] **Database Schema**: Migrations match dub-main Prisma schema patterns
- [ ] **API Endpoints**: Routes mirror dub-main API structure (`/Users/yasinboelhouwer/shorts/dub-main/apps/web/app/api/`)
- [ ] **Models**: Eloquent models have proper relationships matching dub-main patterns
- [ ] **Services**: Business logic correctly implements dub-main patterns from `/lib/` directory
- [ ] **Validation**: Form Requests provide comprehensive validation matching dub-main schemas
- [ ] **Authentication**: Laravel Sanctum properly configured following dub-main auth patterns

### Phase 2: Frontend Migration Review
- [ ] **Source Verification**: Components migrated from correct dub-main source files
- [ ] **Pattern Adaptation**: Next.js patterns properly adapted to Inertia.js
- [ ] **Visual Consistency**: Maintains exact visual alignment with dub-main
- [ ] **Functional Parity**: Behavior matches dub-main equivalent functionality

## **PRIORITY: Dub.co Design Compliance Review**

### ✅ Dub.co Reference Validation (MANDATORY)
- [ ] **Component Research**: Reviewer has examined corresponding dub-main component in `/Users/yasinboelhouwer/shorts/dub-main/`
- [ ] **Migration Documentation**: Component mapping documented with source file references
- [ ] **Visual Consistency**: Component visually matches dub-main equivalent
- [ ] **Pattern Compliance**: Layout, styling, and interaction patterns follow dub-main
- [ ] **Props Structure**: Component props interface follows dub-main patterns
- [ ] **Responsive Design**: Breakpoints and responsive behavior match dub-main
- [ ] **Accessibility**: ARIA attributes and keyboard navigation match dub-main standards
- [ ] **Adaptation Notes**: Next.js to Inertia.js adaptations properly documented
- [ ] **Animation/Transitions**: Hover states and animations follow dub-main timing
- [ ] **Documentation**: Reference component is documented in code comments

### ✅ Architecture Adaptation Review
- [ ] **Next.js to Inertia.js**: Router patterns properly adapted to Inertia navigation
- [ ] **Data Fetching**: Uses Inertia props instead of Next.js data fetching
- [ ] **Form Handling**: Uses Inertia useForm instead of Next.js form patterns
- [ ] **Authentication**: Adapted to Laravel authentication patterns
- [ ] **Performance**: No performance regressions from dub-main patterns

## General Review Principles

### ✅ Before Starting Review
- [ ] Pull request has a clear title and description
- [ ] All CI/CD checks are passing
- [ ] Branch is up to date with target branch
- [ ] No merge conflicts exist
- [ ] Appropriate reviewers are assigned

### ✅ Code Organization
- [ ] Files are placed in appropriate directories
- [ ] Naming conventions are followed consistently
- [ ] Code follows established project patterns
- [ ] No duplicate code without justification
- [ ] Imports are organized and unused imports removed

## PHP/Laravel Backend Review

### ✅ Code Structure & Architecture
- [ ] Controllers are thin and delegate to services
- [ ] Business logic is in appropriate service classes
- [ ] Single Responsibility Principle is followed
- [ ] Dependency injection is used properly
- [ ] Interfaces are used for testability
- [ ] Actions are focused and single-purpose

### ✅ Laravel Conventions
- [ ] Eloquent relationships are defined correctly
- [ ] Route definitions follow RESTful conventions
- [ ] Middleware is applied appropriately
- [ ] Form Requests are used for validation
- [ ] Policies are implemented for authorization
- [ ] Resource classes are used for API responses (if applicable)

### ✅ Database & Models
- [ ] Models use appropriate traits (SoftDeletes, HasFactory, etc.)
- [ ] Fillable/guarded properties are defined
- [ ] Hidden fields are specified for sensitive data
- [ ] Casts are defined for data transformation
- [ ] Relationships are properly defined
- [ ] Database migrations include proper rollback logic
- [ ] Foreign key constraints are used
- [ ] Indexes are added for frequently queried columns

### ✅ Security - PHP
- [ ] Input validation is comprehensive using Form Requests
- [ ] SQL injection prevention (using Eloquent/Query Builder)
- [ ] XSS prevention (proper escaping)
- [ ] CSRF protection is maintained
- [ ] Authorization checks are implemented
- [ ] Sensitive data is not logged
- [ ] Environment variables are used for secrets

### ✅ Performance - PHP
- [ ] N+1 queries are prevented with eager loading
- [ ] Database queries are optimized
- [ ] Caching is implemented where appropriate
- [ ] Pagination is used for large datasets
- [ ] Unnecessary data is not loaded
- [ ] Database transactions are used for multi-table operations

### ✅ Error Handling - PHP
- [ ] Exceptions are caught and handled appropriately
- [ ] User-friendly error messages are provided
- [ ] Errors are logged with appropriate context
- [ ] HTTP status codes are correct
- [ ] Validation errors are returned properly

```php
// ✅ Good: Error handling
try {
    $user = $this->userService->createUser($request->validated());
    return redirect()->route('users.show', $user);
} catch (ValidationException $e) {
    return back()->withErrors($e->errors());
} catch (Exception $e) {
    Log::error('User creation failed', [
        'error' => $e->getMessage(),
        'user_id' => auth()->id(),
    ]);
    return back()->with('error', 'Failed to create user.');
}
```

### ✅ Testing - PHP
- [ ] Feature tests cover HTTP endpoints
- [ ] Unit tests cover business logic
- [ ] Test data uses factories
- [ ] Tests are isolated and don't depend on each other
- [ ] Both success and failure scenarios are tested
- [ ] Test names are descriptive

## TypeScript/React Frontend Review

### ✅ UI/UX Consistency (Dub.co Standards)
- [ ] **Color System**: Uses dub-main neutral palette with TweakCN theme
- [ ] **Typography**: Font hierarchy matches dub-main patterns
- [ ] **Spacing**: Gap and padding follow dub-main patterns (`gap-5 sm:gap-8 md:gap-12`)
- [ ] **Button Variants**: Uses consistent button variants from dub-main
- [ ] **Card Layouts**: Follows CardList component patterns
- [ ] **Empty States**: Uses AnimatedEmptyState patterns
- [ ] **Loading States**: Implements dub-main loading indicators
- [ ] **Interactive States**: Hover and focus states match dub-main behavior

### ✅ Component Structure
- [ ] Components follow single responsibility principle
- [ ] Props are properly typed with interfaces
- [ ] Default exports are used for page components
- [ ] Named exports are used for utility components
- [ ] Components are properly organized in directories

### ✅ TypeScript Usage
- [ ] All props and state are properly typed
- [ ] Interfaces are defined for complex data structures
- [ ] Generic types are used appropriately
- [ ] Type assertions are avoided when possible
- [ ] Strict TypeScript rules are followed
- [ ] No `any` types without justification

### ✅ React Best Practices
- [ ] Hooks are used correctly (Rules of Hooks)
- [ ] useEffect dependencies are correct
- [ ] Event handlers are properly typed
- [ ] Key props are provided for lists
- [ ] Conditional rendering is handled properly
- [ ] State updates are immutable

### ✅ Inertia.js Integration
- [ ] Page components use proper Inertia patterns
- [ ] `useForm` hook is used for all forms
- [ ] Form submissions handle loading states
- [ ] Error handling is implemented
- [ ] `<Head>` component is used for page metadata
- [ ] Route helpers are used correctly

### ✅ UI/UX Considerations
- [ ] Components are accessible (ARIA labels, keyboard navigation)
- [ ] Loading states are shown during async operations
- [ ] Error states are handled gracefully
- [ ] Success feedback is provided to users
- [ ] Responsive design is implemented
- [ ] Color contrast meets accessibility standards

### ✅ Performance - React
- [ ] Components are memoized when appropriate
- [ ] Expensive calculations use `useMemo`
- [ ] Event handlers use `useCallback` when needed
- [ ] Large lists are virtualized if necessary
- [ ] Images are optimized and lazy-loaded
- [ ] Bundle size impact is considered

### ✅ Security - Frontend
- [ ] User input is validated before submission
- [ ] XSS prevention (proper escaping of dynamic content)
- [ ] Sensitive data is not stored in client state
- [ ] API responses are validated
- [ ] Error messages don't expose sensitive information

## Styling & UI Review

### ✅ Tailwind CSS Usage
- [ ] Utility classes are used appropriately
- [ ] Custom CSS is minimal and justified
- [ ] Responsive design is implemented
- [ ] Dark mode support is considered
- [ ] Component variants are handled properly

### ✅ shadcn/ui Integration
- [ ] Components are used correctly
- [ ] Customizations follow the design system
- [ ] Accessibility features are maintained
- [ ] Component composition is logical

## Testing Review

### ✅ Test Coverage
- [ ] Critical business logic is tested
- [ ] Edge cases are covered
- [ ] Both positive and negative scenarios are tested
- [ ] Tests are maintainable and readable
- [ ] Test data is realistic and consistent

### ✅ Test Quality - PHP
- [ ] Use descriptive test method names
- [ ] Test both success and failure scenarios
- [ ] Use factories for test data
- [ ] Assert database changes
- [ ] Test authorization and validation

### ✅ Test Quality - TypeScript
- [ ] Test user interactions, not implementation details
- [ ] Mock external dependencies
- [ ] Use descriptive test descriptions
- [ ] Test both success and error scenarios
- [ ] Use proper assertions and expectations

## Documentation Review

### ✅ Code Documentation
- [ ] Complex logic is commented
- [ ] Public methods have docblocks
- [ ] README is updated if necessary
- [ ] API changes are documented
- [ ] Breaking changes are highlighted

### ✅ Commit Messages
- [ ] Follow conventional commit format
- [ ] Are descriptive and clear
- [ ] Reference issue numbers when applicable
- [ ] Explain the "why" not just the "what"

## Final Review Checklist

### ✅ Before Approval
- [ ] All automated checks pass
- [ ] Code follows project standards
- [ ] No obvious bugs or issues
- [ ] Performance implications are considered
- [ ] Security concerns are addressed
- [ ] Tests are adequate and passing
- [ ] Documentation is updated
- [ ] Breaking changes are communicated

### ✅ Reviewer Actions
- [ ] Provide constructive feedback
- [ ] Suggest improvements, not just point out problems
- [ ] Ask questions for clarification
- [ ] Acknowledge good practices
- [ ] Test the changes locally if needed

### ✅ Author Responsibilities
- [ ] Address all review comments
- [ ] Explain decisions when requested
- [ ] Update code based on feedback
- [ ] Ensure all discussions are resolved
- [ ] Thank reviewers for their time

## Common Issues to Watch For

### ❌ PHP Anti-patterns
- Fat controllers with business logic
- N+1 queries without eager loading
- Missing input validation
- Direct request data usage without validation
- Missing authorization checks

### ❌ React Anti-patterns
- Missing useEffect dependencies
- Direct state mutation
- Missing error handling in forms
- Improper key props in lists
- Missing TypeScript types

### ❌ General Anti-patterns
- Hardcoded values instead of configuration
- Missing error handling
- Inconsistent naming conventions
- Overly complex functions
- Missing type definitions
- Inadequate test coverage
- Security vulnerabilities
- Performance bottlenecks

## Review Outcome Actions

### ✅ Approve
- Code meets all standards
- No blocking issues
- Minor suggestions can be addressed in follow-up

### 🔄 Request Changes
- Blocking issues must be resolved
- Security or performance concerns
- Standards violations
- Inadequate testing

### 💬 Comment
- Non-blocking suggestions
- Questions for clarification
- Educational feedback
- Praise for good practices

Remember: The goal of code review is to maintain code quality, share knowledge, and prevent bugs from reaching production. Be thorough but constructive in your reviews.
