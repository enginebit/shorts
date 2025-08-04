---
type: "always_apply"
---

# Coding Standards - Laravel + React + Inertia.js

## Backend-First Strategy
**Phase 1**: Laravel backend (API, models, business logic) → **Phase 2**: React components from `/Users/yasinboelhouwer/shorts/dub-main/`

**CRITICAL**: Always reference `dub-main` repository for UI/UX patterns, component structures, and API designs before implementation.

## PHP/Laravel Standards

### File Organization
```
app/Http/Controllers/    # Feature-based controllers
app/Models/             # Eloquent models
app/Services/           # Business logic
app/Actions/            # Single-purpose actions
app/Http/Requests/      # Form validation
```

### Naming Conventions
- **Classes**: PascalCase (`UserController`)
- **Methods**: camelCase (`createUser`)
- **Variables**: camelCase (`$userData`)
- **Constants**: SCREAMING_SNAKE_CASE (`MAX_SIZE`)
- **Tables**: snake_case plural (`users`)
- **Columns**: snake_case (`created_at`)
- **Routes**: kebab-case (`/user-profiles`)

### PHP Standards
- Use PHP 8.2+ features (readonly properties, enums, match expressions)
- Always use strict types: `declare(strict_types=1);`
- Use constructor property promotion
- Prefer readonly properties when possible
- Use typed properties and return types
- Use final classes unless inheritance is intended
- Use dependency injection over facades in classes

### Laravel Conventions
- Use Eloquent relationships over raw queries
- Implement Form Requests for validation
- Use Resource Controllers for CRUD operations
- Implement Service classes for complex business logic
- Use Actions for single-purpose operations
- Follow RESTful routing conventions

## TypeScript/React Standards

### File Organization
```
resources/js/
├── components/
│   ├── ui/                # shadcn/ui components
│   ├── forms/             # Form components
│   ├── layout/            # Layout components
│   └── shared/            # Reusable components
├── pages/                 # Inertia page components
├── hooks/                 # Custom React hooks
├── lib/                   # Utility functions
├── types/                 # TypeScript definitions
└── constants/             # Application constants
```

### Naming Conventions
- **Components**: PascalCase (`UserProfile.tsx`, `CreateUserForm.tsx`)
- **Files**: kebab-case for non-components (`user-utils.ts`, `api-client.ts`)
- **Variables/Functions**: camelCase (`userData`, `handleSubmit`)
- **Constants**: SCREAMING_SNAKE_CASE (`API_BASE_URL`)
- **Types/Interfaces**: PascalCase (`User`, `CreateUserData`)
- **Hooks**: camelCase starting with 'use' (`useUserData`, `useFormValidation`)

### TypeScript Standards
- Use strict TypeScript configuration
- Define interfaces for all props and data structures
- Use generic types for reusable components
- Prefer `interface` over `type` for object shapes
- Use `const assertions` for immutable data
- Implement proper error boundaries
- Use discriminated unions for complex state

### React Standards
- Use functional components with hooks
- Implement proper key props for lists
- Use React.memo() for expensive components
- Implement proper cleanup in useEffect
- Use custom hooks for reusable logic
- Follow the Rules of Hooks
- Use TypeScript for prop validation instead of PropTypes

## Inertia.js Standards

### Page Components
- Place in `resources/js/pages/` with nested folders matching routes
- Use default exports for page components
- Define TypeScript interfaces for page props
- Include `<Head>` component for page metadata

### Form Handling
```typescript
// Use Inertia's useForm hook for all forms
const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    email: '',
});

// Handle form submission
const handleSubmit: FormEventHandler = (e) => {
    e.preventDefault();
    post(route('users.store'), {
        onSuccess: () => reset(),
        preserveScroll: true,
    });
};
```

## Database Standards

### Migrations
- Use descriptive migration names
- Include rollback logic in down() method
- Use foreign key constraints
- Add indexes for frequently queried columns
- Use soft deletes for user data

### Models
```php
final class UserProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'bio',
        'avatar_url',
        'preferences',
    ];

    protected $casts = [
        'preferences' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

## Testing Standards

### PHP Tests
- Use Feature tests for HTTP endpoints
- Use Unit tests for isolated logic
- Use factories for test data
- Test both success and failure scenarios
- Use descriptive test method names

### TypeScript Tests
- Use Vitest for unit testing
- Use React Testing Library for component tests
- Test user interactions, not implementation details
- Mock external dependencies
- Use descriptive test descriptions

## Import Organization

### PHP
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

// Laravel core imports
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

// Third-party imports
use Inertia\Inertia;
use Inertia\Response;

// Application imports
use App\Http\Requests\CreateUserRequest;
use App\Models\User;
use App\Services\UserService;
```

### TypeScript
```typescript
// React imports
import { useState, useEffect } from 'react';

// Third-party imports
import { Head, useForm } from '@inertiajs/react';

// UI component imports
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

// Local imports
import AppLayout from '@/layouts/app-layout';
import { User } from '@/types';
```

## Error Handling

### PHP
```php
try {
    $user = $this->userService->createUser($data);
    return redirect()->route('users.show', $user);
} catch (ValidationException $e) {
    return back()->withErrors($e->errors());
} catch (Exception $e) {
    Log::error('User creation failed', ['error' => $e->getMessage()]);
    return back()->with('error', 'Failed to create user.');
}
```

### TypeScript
```typescript
const handleSubmit: FormEventHandler = (e) => {
    e.preventDefault();

    post(route('users.store'), {
        onError: (errors) => {
            // Handle validation errors
            console.error('Validation errors:', errors);
        },
        onSuccess: () => {
            // Handle success
            reset();
        },
    });
};
```

## Performance Guidelines

### PHP
- Use eager loading to prevent N+1 queries
- Implement database indexes for frequently queried columns
- Use caching for expensive operations
- Optimize database queries with `select()` to limit columns

### React
- Use React.memo() for expensive components
- Implement proper key props for lists
- Use useMemo() and useCallback() judiciously
- Lazy load components when appropriate
- Optimize bundle size with proper imports

## Security Standards

### PHP
- Use Form Requests for input validation
- Implement CSRF protection (enabled by default)
- Use parameterized queries (Eloquent handles this)
- Validate and sanitize all user input
- Implement proper authorization checks

### TypeScript
- Validate data received from backend
- Sanitize user input before display
- Use TypeScript for type safety
- Implement proper error boundaries
- Avoid storing sensitive data in client state
