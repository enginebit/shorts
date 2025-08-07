/// <reference types="vitest" />
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  esbuild: {
    jsx: 'automatic',
  },
  test: {
    // Test environment configuration
    environment: 'jsdom',

    // Global test setup
    globals: true,
    setupFiles: ['./tests/frontend/setup.ts'],

    // Test file patterns
    include: [
      'tests/frontend/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'resources/js/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}'
    ],

    // Coverage configuration
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html', 'lcov'],
      reportsDirectory: './coverage',
      include: [
        'resources/js/**/*.{js,ts,jsx,tsx}',
      ],
      exclude: [
        'resources/js/**/*.d.ts',
        'resources/js/**/*.config.{js,ts}',
        'resources/js/**/*.test.{js,ts,jsx,tsx}',
        'resources/js/**/*.spec.{js,ts,jsx,tsx}',
        'resources/js/bootstrap.ts',
        'resources/js/ziggy.js',
      ],
      thresholds: {
        global: {
          branches: 70,
          functions: 70,
          lines: 70,
          statements: 70
        }
      }
    },

    // Test timeout
    testTimeout: 10000,

    // Reporter configuration
    reporter: ['verbose', 'json', 'html'],
    outputFile: {
      json: './test-results/vitest-results.json',
      html: './test-results/index.html'
    },

    // Mock configuration
    deps: {
      inline: ['@inertiajs/react']
    },

    // Environment variables for testing
    env: {
      NODE_ENV: 'test',
      VITE_APP_NAME: 'Shorts Test',
    }
  },

  // Resolve configuration for tests
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './resources/js'),
      '@/components': path.resolve(__dirname, './resources/js/components'),
      '@/pages': path.resolve(__dirname, './resources/js/pages'),
      '@/layouts': path.resolve(__dirname, './resources/js/layouts'),
      '@/hooks': path.resolve(__dirname, './resources/js/hooks'),
      '@/lib': path.resolve(__dirname, './resources/js/lib'),
      '@/types': path.resolve(__dirname, './resources/js/types'),
    },
  },

  // Define globals for testing
  define: {
    global: 'globalThis',
  },
})
