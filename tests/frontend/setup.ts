import '@testing-library/jest-dom'
import React from 'react'
import { expect, afterEach, vi } from 'vitest'
import { cleanup } from '@testing-library/react'

// Cleanup after each test case (e.g. clearing jsdom)
afterEach(() => {
  cleanup()
})

// Mock Inertia.js
vi.mock('@inertiajs/react', () => ({
  Head: ({ children, title }: { children?: React.ReactNode; title?: string }) => {
    if (title) {
      return React.createElement('title', null, title)
    }
    return React.createElement(React.Fragment, null, children)
  },
  Link: ({ href, children, ...props }: any) => {
    return React.createElement('a', { href, ...props }, children)
  },
  useForm: () => ({
    data: {},
    setData: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    get: vi.fn(),
    processing: false,
    errors: {},
    hasErrors: false,
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
  }),
  usePage: () => ({
    props: {
      auth: {
        user: null,
      },
      flash: {},
      errors: {},
    },
    url: '/',
    component: 'Test',
    version: '1',
  }),
  router: {
    visit: vi.fn(),
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
    reload: vi.fn(),
    replace: vi.fn(),
    remember: vi.fn(),
    restore: vi.fn(),
    on: vi.fn(),
    off: vi.fn(),
  },
}))

// Mock Ziggy route helper
vi.mock('ziggy-js', () => ({
  route: vi.fn((name: string, params?: any) => {
    const routes: Record<string, string> = {
      'dashboard': '/dashboard',
      'login': '/login',
      'register': '/register',
      'logout': '/logout',
      'links.index': '/links',
      'links.create': '/links/create',
      'links.show': '/links/:id',
      'links.edit': '/links/:id/edit',
      'workspaces.index': '/workspaces',
      'workspaces.show': '/workspaces/:id',
    }

    let url = routes[name] || `/${name}`

    if (params && typeof params === 'object') {
      Object.keys(params).forEach(key => {
        url = url.replace(`:${key}`, params[key])
      })
    }

    return url
  }),
}))

// Mock window.matchMedia
Object.defineProperty(window, 'matchMedia', {
  writable: true,
  value: vi.fn().mockImplementation(query => ({
    matches: false,
    media: query,
    onchange: null,
    addListener: vi.fn(), // deprecated
    removeListener: vi.fn(), // deprecated
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  })),
})

// Mock ResizeObserver
global.ResizeObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}))

// Mock IntersectionObserver
global.IntersectionObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}))

// Mock scrollTo
Object.defineProperty(window, 'scrollTo', {
  value: vi.fn(),
  writable: true,
})

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
})

// Mock sessionStorage
const sessionStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
Object.defineProperty(window, 'sessionStorage', {
  value: sessionStorageMock,
})

// Mock fetch
global.fetch = vi.fn()

// Mock console methods to reduce noise in tests
global.console = {
  ...console,
  log: vi.fn(),
  debug: vi.fn(),
  info: vi.fn(),
  warn: vi.fn(),
  error: vi.fn(),
}

// Custom matchers
expect.extend({
  toBeInTheDocument: (received) => {
    const pass = received && document.body.contains(received)
    return {
      message: () => `expected element ${pass ? 'not ' : ''}to be in the document`,
      pass,
    }
  },
})
