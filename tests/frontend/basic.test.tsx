import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import React from 'react'

// Simple test component
const TestComponent = ({ message }: { message: string }) => {
  return <div data-testid="test-message">{message}</div>
}

describe('Basic Frontend Testing', () => {
  it('renders a simple component', () => {
    render(<TestComponent message="Hello, World!" />)
    
    expect(screen.getByTestId('test-message')).toBeInTheDocument()
    expect(screen.getByText('Hello, World!')).toBeInTheDocument()
  })

  it('handles props correctly', () => {
    const testMessage = 'Testing props'
    render(<TestComponent message={testMessage} />)
    
    expect(screen.getByText(testMessage)).toBeInTheDocument()
  })

  it('verifies testing environment is working', () => {
    // Test that our testing utilities work
    expect(true).toBe(true)
    expect('test').toMatch(/test/)
    expect([1, 2, 3]).toHaveLength(3)
  })

  it('verifies DOM testing capabilities', () => {
    render(
      <div>
        <h1>Test Heading</h1>
        <button type="button">Test Button</button>
        <input type="text" placeholder="Test input" />
      </div>
    )

    expect(screen.getByRole('heading', { name: 'Test Heading' })).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Test Button' })).toBeInTheDocument()
    expect(screen.getByPlaceholderText('Test input')).toBeInTheDocument()
  })

  it('verifies async testing capabilities', async () => {
    const AsyncComponent = () => {
      const [message, setMessage] = React.useState('Loading...')
      
      React.useEffect(() => {
        setTimeout(() => {
          setMessage('Loaded!')
        }, 100)
      }, [])
      
      return <div data-testid="async-message">{message}</div>
    }

    render(<AsyncComponent />)
    
    expect(screen.getByText('Loading...')).toBeInTheDocument()
    
    // Wait for async update
    await screen.findByText('Loaded!')
    expect(screen.getByText('Loaded!')).toBeInTheDocument()
  })
})
