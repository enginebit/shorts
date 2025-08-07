import { describe, it, expect, vi } from 'vitest'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render } from '../../utils/test-utils'
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
    
    const button = screen.getByRole('button')
    await user.click(button)
    
    expect(handleClick).toHaveBeenCalledTimes(1)
  })

  it('applies primary variant styles', () => {
    render(<Button variant="primary">Primary Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveClass('bg-black', 'text-white')
  })

  it('applies secondary variant styles', () => {
    render(<Button variant="secondary">Secondary Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveClass('border', 'bg-white')
  })

  it('applies danger variant styles', () => {
    render(<Button variant="danger">Danger Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveClass('bg-red-600', 'text-white')
  })

  it('applies small size styles', () => {
    render(<Button size="sm">Small Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveClass('h-8', 'px-3', 'text-sm')
  })

  it('applies large size styles', () => {
    render(<Button size="lg">Large Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveClass('h-12', 'px-8', 'text-base')
  })

  it('shows loading state', () => {
    render(<Button loading>Loading Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toBeDisabled()
    expect(screen.getByText('Loading Button')).toBeInTheDocument()
    // Should show loading spinner
  })

  it('is disabled when disabled prop is true', () => {
    render(<Button disabled>Disabled Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toBeDisabled()
    expect(button).toHaveClass('opacity-50', 'cursor-not-allowed')
  })

  it('prevents click when disabled', async () => {
    const handleClick = vi.fn()
    const user = userEvent.setup()
    
    render(<Button disabled onClick={handleClick}>Disabled Button</Button>)
    
    const button = screen.getByRole('button')
    await user.click(button)
    
    expect(handleClick).not.toHaveBeenCalled()
  })

  it('prevents click when loading', async () => {
    const handleClick = vi.fn()
    const user = userEvent.setup()
    
    render(<Button loading onClick={handleClick}>Loading Button</Button>)
    
    const button = screen.getByRole('button')
    await user.click(button)
    
    expect(handleClick).not.toHaveBeenCalled()
  })

  it('renders as link when href is provided', () => {
    render(<Button href="/test">Link Button</Button>)
    
    const link = screen.getByRole('link', { name: 'Link Button' })
    expect(link).toHaveAttribute('href', '/test')
  })

  it('applies custom className', () => {
    render(<Button className="custom-class">Custom Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveClass('custom-class')
  })

  it('forwards ref correctly', () => {
    const ref = vi.fn()
    
    render(<Button ref={ref}>Ref Button</Button>)
    
    expect(ref).toHaveBeenCalled()
  })

  it('supports icon buttons', () => {
    const Icon = () => <svg data-testid="icon" />
    
    render(
      <Button>
        <Icon />
        Icon Button
      </Button>
    )
    
    expect(screen.getByTestId('icon')).toBeInTheDocument()
    expect(screen.getByText('Icon Button')).toBeInTheDocument()
  })

  it('supports icon-only buttons', () => {
    const Icon = () => <svg data-testid="icon" aria-label="Settings" />
    
    render(
      <Button aria-label="Settings">
        <Icon />
      </Button>
    )
    
    expect(screen.getByTestId('icon')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: 'Settings' })).toBeInTheDocument()
  })

  it('handles keyboard navigation', async () => {
    const handleClick = vi.fn()
    const user = userEvent.setup()
    
    render(<Button onClick={handleClick}>Keyboard Button</Button>)
    
    const button = screen.getByRole('button')
    button.focus()
    
    await user.keyboard('{Enter}')
    expect(handleClick).toHaveBeenCalledTimes(1)
    
    await user.keyboard(' ')
    expect(handleClick).toHaveBeenCalledTimes(2)
  })

  it('has proper focus styles', () => {
    render(<Button>Focus Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveClass('focus:outline-none', 'focus:ring-2')
  })

  it('supports full width', () => {
    render(<Button fullWidth>Full Width Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveClass('w-full')
  })

  it('renders with proper type attribute', () => {
    render(<Button type="submit">Submit Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveAttribute('type', 'submit')
  })

  it('defaults to button type', () => {
    render(<Button>Default Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveAttribute('type', 'button')
  })

  it('supports custom loading text', () => {
    render(<Button loading loadingText="Saving...">Save</Button>)
    
    expect(screen.getByText('Saving...')).toBeInTheDocument()
  })

  it('maintains accessibility with loading state', () => {
    render(<Button loading>Loading Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveAttribute('aria-disabled', 'true')
  })

  it('supports ghost variant', () => {
    render(<Button variant="ghost">Ghost Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveClass('hover:bg-gray-100')
  })

  it('supports outline variant', () => {
    render(<Button variant="outline">Outline Button</Button>)
    
    const button = screen.getByRole('button')
    expect(button).toHaveClass('border', 'border-gray-300')
  })
})
