# Frontend Testing with Vitest

This document describes the comprehensive frontend testing setup for our Laravel + React + Inertia.js URL shortener application using Vitest, React Testing Library, and jsdom.

## Overview

Our frontend testing implementation addresses the critical gap identified in our analysis compared to dub-main's extensive frontend testing. We use modern testing tools that provide excellent developer experience and comprehensive coverage.

## Testing Stack

### Core Testing Framework
- **Vitest**: Fast, modern test runner with native TypeScript support
- **React Testing Library**: Testing utilities focused on user behavior
- **jsdom**: DOM implementation for Node.js testing environment
- **@testing-library/jest-dom**: Custom matchers for DOM assertions
- **@testing-library/user-event**: User interaction simulation

### Key Features
- **TypeScript Support**: Full TypeScript integration with type checking
- **JSX Support**: Automatic JSX transformation for React components
- **Coverage Reporting**: Built-in code coverage with multiple formats
- **Watch Mode**: Real-time test execution during development
- **UI Mode**: Interactive test runner interface
- **Mocking**: Comprehensive mocking capabilities for Inertia.js and external dependencies

## Configuration

### Vitest Configuration (`vitest.config.ts`)

```typescript
export default defineConfig({
  plugins: [react()],
  esbuild: {
    jsx: 'automatic',
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./tests/frontend/setup.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html', 'lcov'],
      thresholds: {
        global: {
          branches: 70,
          functions: 70,
          lines: 70,
          statements: 70
        }
      }
    }
  }
})
```

### Test Setup (`tests/frontend/setup.ts`)

The setup file provides:
- **Inertia.js Mocking**: Complete mock implementation of Inertia.js hooks and components
- **Global Cleanup**: Automatic cleanup after each test
- **Browser API Mocks**: ResizeObserver, IntersectionObserver, matchMedia, localStorage
- **Route Helper Mocking**: Ziggy route helper with common routes
- **Console Mocking**: Reduced noise during test execution

## Test Utilities

### Custom Test Utils (`tests/frontend/utils/test-utils.tsx`)

Provides helper functions for testing:

```typescript
// Mock data creators
const mockUser = createMockUser({ name: 'Test User' })
const mockWorkspace = createMockWorkspace({ name: 'Test Workspace' })
const mockLink = createMockLink({ key: 'abc123', clicks: 150 })

// Form submission mocking
const successfulForm = mockSuccessfulFormSubmission()
const failedForm = mockFailedFormSubmission({ email: 'Required field' })

// Custom render with Inertia props
render(<Component />, {
  pageProps: {
    auth: { user: mockUser },
    flash: { success: 'Operation successful' }
  }
})
```

## Available Scripts

### Development Scripts
```bash
# Run tests in watch mode
npm run test:frontend

# Run tests with UI interface
npm run test:frontend:ui

# Run tests once
npm run test:frontend:run

# Run tests with coverage
npm run test:frontend:coverage

# Run all tests (backend + frontend)
npm run test:all
```

## Test Structure

### Test File Organization
```
tests/frontend/
├── setup.ts                    # Global test setup
├── utils/
│   └── test-utils.tsx          # Custom testing utilities
├── components/
│   └── ui/
│       └── button.test.tsx     # UI component tests
├── pages/
│   ├── auth/
│   │   ├── login.test.tsx      # Authentication page tests
│   │   └── register.test.tsx
│   └── dashboard.test.tsx      # Dashboard page tests
└── basic.test.tsx              # Basic functionality tests
```

### Test Categories

1. **Component Tests**: Test individual UI components in isolation
2. **Page Tests**: Test complete page components with Inertia.js integration
3. **Integration Tests**: Test component interactions and data flow
4. **Accessibility Tests**: Verify WCAG compliance and screen reader support

## Writing Tests

### Basic Component Test

```typescript
import { describe, it, expect } from 'vitest'
import { render, screen } from '../utils/test-utils'
import { Button } from '@/components/ui/button'

describe('Button Component', () => {
  it('renders button with text', () => {
    render(<Button>Click me</Button>)
    
    expect(screen.getByRole('button', { name: 'Click me' })).toBeInTheDocument()
  })

  it('handles click events', async () => {
    const handleClick = vi.fn()
    const user = userEvent.setup()
    
    render(<Button onClick={handleClick}>Click me</Button>)
    
    await user.click(screen.getByRole('button'))
    expect(handleClick).toHaveBeenCalledTimes(1)
  })
})
```

### Inertia.js Page Test

```typescript
import { describe, it, expect } from 'vitest'
import { render, screen, createMockUser } from '../utils/test-utils'
import Dashboard from '@/pages/dashboard'

describe('Dashboard Page', () => {
  it('renders dashboard for authenticated user', () => {
    const mockUser = createMockUser()
    
    render(<Dashboard />, {
      pageProps: {
        auth: { user: mockUser },
      }
    })

    expect(screen.getByText(`Welcome back, ${mockUser.name}`)).toBeInTheDocument()
  })
})
```

### Form Testing

```typescript
import { describe, it, expect, vi } from 'vitest'
import { render, screen, mockSuccessfulFormSubmission } from '../utils/test-utils'
import LoginForm from '@/components/auth/login-form'

// Mock useForm hook
vi.mock('@inertiajs/react', async () => {
  const actual = await vi.importActual('@inertiajs/react')
  return {
    ...actual,
    useForm: () => mockSuccessfulFormSubmission(),
  }
})

describe('Login Form', () => {
  it('submits form with correct data', async () => {
    const user = userEvent.setup()
    render(<LoginForm />)

    await user.type(screen.getByLabelText(/email/i), 'test@example.com')
    await user.type(screen.getByLabelText(/password/i), 'password123')
    await user.click(screen.getByRole('button', { name: /sign in/i }))

    // Verify form submission
  })
})
```

## Mocking Strategies

### Inertia.js Mocking

The setup automatically mocks all Inertia.js functionality:
- `useForm` hook with customizable return values
- `usePage` hook with mock page props
- `Head` component for page metadata
- `Link` component for navigation
- `router` object for programmatic navigation

### External Dependencies

Mock external libraries as needed:

```typescript
// Mock external API
vi.mock('@/lib/api', () => ({
  fetchUserData: vi.fn().mockResolvedValue({ id: 1, name: 'Test User' }),
}))

// Mock browser APIs
Object.defineProperty(window, 'localStorage', {
  value: {
    getItem: vi.fn(),
    setItem: vi.fn(),
    removeItem: vi.fn(),
    clear: vi.fn(),
  },
})
```

## Coverage Requirements

### Coverage Thresholds
- **Branches**: 70% minimum
- **Functions**: 70% minimum  
- **Lines**: 70% minimum
- **Statements**: 70% minimum

### Coverage Reports
- **Text**: Console output during test runs
- **HTML**: Interactive coverage report in `./coverage/index.html`
- **JSON**: Machine-readable coverage data
- **LCOV**: For CI/CD integration

## Best Practices

### Test Writing Guidelines

1. **Test User Behavior**: Focus on what users do, not implementation details
2. **Use Semantic Queries**: Prefer `getByRole`, `getByLabelText` over `getByTestId`
3. **Test Accessibility**: Verify ARIA attributes and keyboard navigation
4. **Mock External Dependencies**: Keep tests isolated and fast
5. **Use Descriptive Names**: Test names should clearly describe the scenario

### Performance Optimization

1. **Selective Test Running**: Use file patterns to run specific tests
2. **Parallel Execution**: Vitest runs tests in parallel by default
3. **Efficient Mocking**: Mock only what's necessary for each test
4. **Cleanup**: Ensure proper cleanup to prevent test interference

### Debugging Tests

```bash
# Run tests with verbose output
npm run test:frontend -- --reporter=verbose

# Run specific test file
npm run test:frontend -- tests/frontend/components/ui/button.test.tsx

# Run tests matching pattern
npm run test:frontend -- --grep "Button Component"

# Debug with UI
npm run test:frontend:ui
```

## Integration with CI/CD

### GitHub Actions Integration

```yaml
- name: Run Frontend Tests
  run: npm run test:frontend:run

- name: Generate Coverage Report
  run: npm run test:frontend:coverage

- name: Upload Coverage
  uses: codecov/codecov-action@v3
  with:
    file: ./coverage/lcov.info
```

## Comparison with Dub-Main

### Advantages of Our Implementation

1. **Modern Testing Stack**: Vitest is faster and more modern than Jest
2. **TypeScript Integration**: Better TypeScript support than dub-main's setup
3. **Inertia.js Testing**: Specialized utilities for Inertia.js applications
4. **Laravel Integration**: Seamless integration with Laravel backend testing

### Dub-Main Patterns Adopted

1. **Comprehensive Coverage**: Extensive test coverage like dub-main
2. **Component Testing**: Individual component testing approach
3. **User-Centric Testing**: Focus on user behavior over implementation
4. **Accessibility Testing**: WCAG compliance verification

## Future Enhancements

1. **Visual Regression Testing**: Add screenshot comparison tests
2. **E2E Testing**: Implement Playwright for end-to-end testing
3. **Performance Testing**: Add performance benchmarking
4. **A11y Testing**: Enhanced accessibility testing with axe-core

This frontend testing setup provides robust coverage and excellent developer experience while maintaining compatibility with our Laravel + React + Inertia.js architecture.
