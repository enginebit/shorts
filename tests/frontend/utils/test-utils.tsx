import React, { ReactElement } from 'react'
import { render, RenderOptions } from '@testing-library/react'
import { vi } from 'vitest'

// Mock Inertia page props
export interface MockPageProps {
  auth?: {
    user?: {
      id: string
      name: string
      email: string
      email_verified_at?: string
      image?: string
    } | null
  }
  flash?: {
    success?: string
    error?: string
    warning?: string
    info?: string
  }
  errors?: Record<string, string>
  [key: string]: any
}

// Default mock props
const defaultProps: MockPageProps = {
  auth: {
    user: null,
  },
  flash: {},
  errors: {},
}

// Mock Inertia context
const mockInertiaContext = {
  props: defaultProps,
  url: '/',
  component: 'Test',
  version: '1',
}

// Test wrapper component
interface TestWrapperProps {
  children: React.ReactNode
  pageProps?: MockPageProps
}

const TestWrapper: React.FC<TestWrapperProps> = ({ 
  children, 
  pageProps = defaultProps 
}) => {
  // Mock usePage hook for this test
  vi.doMock('@inertiajs/react', async () => {
    const actual = await vi.importActual('@inertiajs/react')
    return {
      ...actual,
      usePage: () => ({
        ...mockInertiaContext,
        props: { ...defaultProps, ...pageProps },
      }),
    }
  })

  return <>{children}</>
}

// Custom render function
const customRender = (
  ui: ReactElement,
  options?: Omit<RenderOptions, 'wrapper'> & {
    pageProps?: MockPageProps
  }
) => {
  const { pageProps, ...renderOptions } = options || {}
  
  return render(ui, {
    wrapper: ({ children }) => (
      <TestWrapper pageProps={pageProps}>{children}</TestWrapper>
    ),
    ...renderOptions,
  })
}

// Helper to create authenticated user
export const createMockUser = (overrides = {}) => ({
  id: 'user_123',
  name: 'Test User',
  email: 'test@example.com',
  email_verified_at: '2024-01-01T00:00:00.000Z',
  image: null,
  ...overrides,
})

// Helper to create mock workspace
export const createMockWorkspace = (overrides = {}) => ({
  id: 'workspace_123',
  name: 'Test Workspace',
  slug: 'test-workspace',
  plan: 'free',
  usage: {
    links: 0,
    clicks: 0,
  },
  ...overrides,
})

// Helper to create mock link
export const createMockLink = (overrides = {}) => ({
  id: 'link_123',
  url: 'https://example.com',
  key: 'abc123',
  domain: 'dub.sh',
  title: 'Example Link',
  description: 'An example link for testing',
  image: null,
  clicks: 0,
  created_at: '2024-01-01T00:00:00.000Z',
  updated_at: '2024-01-01T00:00:00.000Z',
  ...overrides,
})

// Helper to create mock form errors
export const createMockErrors = (fields: string[]) => {
  return fields.reduce((acc, field) => {
    acc[field] = `The ${field} field is required.`
    return acc
  }, {} as Record<string, string>)
}

// Helper to create mock flash messages
export const createMockFlash = (type: 'success' | 'error' | 'warning' | 'info', message: string) => ({
  [type]: message,
})

// Helper to mock successful form submission
export const mockSuccessfulFormSubmission = () => ({
  data: {},
  setData: vi.fn(),
  post: vi.fn().mockImplementation((url, options) => {
    if (options?.onSuccess) {
      options.onSuccess({})
    }
  }),
  put: vi.fn().mockImplementation((url, options) => {
    if (options?.onSuccess) {
      options.onSuccess({})
    }
  }),
  patch: vi.fn().mockImplementation((url, options) => {
    if (options?.onSuccess) {
      options.onSuccess({})
    }
  }),
  delete: vi.fn().mockImplementation((url, options) => {
    if (options?.onSuccess) {
      options.onSuccess({})
    }
  }),
  processing: false,
  errors: {},
  hasErrors: false,
  progress: null,
  wasSuccessful: true,
  recentlySuccessful: true,
  reset: vi.fn(),
  clearErrors: vi.fn(),
  setError: vi.fn(),
  transform: vi.fn(),
  defaults: vi.fn(),
  cancel: vi.fn(),
  submit: vi.fn(),
})

// Helper to mock failed form submission
export const mockFailedFormSubmission = (errors: Record<string, string> = {}) => ({
  data: {},
  setData: vi.fn(),
  post: vi.fn().mockImplementation((url, options) => {
    if (options?.onError) {
      options.onError(errors)
    }
  }),
  put: vi.fn().mockImplementation((url, options) => {
    if (options?.onError) {
      options.onError(errors)
    }
  }),
  patch: vi.fn().mockImplementation((url, options) => {
    if (options?.onError) {
      options.onError(errors)
    }
  }),
  delete: vi.fn().mockImplementation((url, options) => {
    if (options?.onError) {
      options.onError(errors)
    }
  }),
  processing: false,
  errors,
  hasErrors: Object.keys(errors).length > 0,
  progress: null,
  wasSuccessful: false,
  recentlySuccessful: false,
  reset: vi.fn(),
  clearErrors: vi.fn(),
  setError: vi.fn(),
  transform: vi.fn(),
  defaults: vi.fn(),
  cancel: vi.fn(),
  submit: vi.fn(),
})

// Helper to wait for async operations
export const waitFor = (callback: () => void, timeout = 1000) => {
  return new Promise<void>((resolve, reject) => {
    const startTime = Date.now()
    
    const check = () => {
      try {
        callback()
        resolve()
      } catch (error) {
        if (Date.now() - startTime >= timeout) {
          reject(error)
        } else {
          setTimeout(check, 10)
        }
      }
    }
    
    check()
  })
}

// Re-export everything from React Testing Library
export * from '@testing-library/react'
export { customRender as render }
