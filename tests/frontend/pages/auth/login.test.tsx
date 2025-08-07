import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen, fireEvent, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render, createMockErrors, mockSuccessfulFormSubmission, mockFailedFormSubmission } from '../../utils/test-utils'
import Login from '@/pages/auth/login'

// Mock the useForm hook
const mockUseForm = vi.fn()
vi.mock('@inertiajs/react', async () => {
  const actual = await vi.importActual('@inertiajs/react')
  return {
    ...actual,
    useForm: () => mockUseForm(),
  }
})

describe('Login Page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders login form correctly', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Login />)

    // Check for form elements
    expect(screen.getByRole('heading', { name: /sign in/i })).toBeInTheDocument()
    expect(screen.getByLabelText(/email/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/password/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument()
    
    // Check for links
    expect(screen.getByText(/don't have an account/i)).toBeInTheDocument()
    expect(screen.getByText(/forgot your password/i)).toBeInTheDocument()
  })

  it('displays OAuth login options', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Login />)

    // Check for OAuth buttons
    expect(screen.getByText(/continue with google/i)).toBeInTheDocument()
    expect(screen.getByText(/continue with github/i)).toBeInTheDocument()
  })

  it('handles form input changes', async () => {
    const mockSetData = vi.fn()
    mockUseForm.mockReturnValue({
      ...mockSuccessfulFormSubmission(),
      setData: mockSetData,
      data: { email: '', password: '' },
    })
    
    const user = userEvent.setup()
    render(<Login />)

    const emailInput = screen.getByLabelText(/email/i)
    const passwordInput = screen.getByLabelText(/password/i)

    await user.type(emailInput, 'test@example.com')
    await user.type(passwordInput, 'password123')

    expect(mockSetData).toHaveBeenCalledWith('email', 'test@example.com')
    expect(mockSetData).toHaveBeenCalledWith('password', 'password123')
  })

  it('submits form with correct data', async () => {
    const mockPost = vi.fn()
    mockUseForm.mockReturnValue({
      ...mockSuccessfulFormSubmission(),
      post: mockPost,
      data: { email: 'test@example.com', password: 'password123' },
    })
    
    const user = userEvent.setup()
    render(<Login />)

    const submitButton = screen.getByRole('button', { name: /sign in/i })
    await user.click(submitButton)

    expect(mockPost).toHaveBeenCalledWith('/login', {
      onFinish: expect.any(Function),
    })
  })

  it('displays validation errors', () => {
    const errors = createMockErrors(['email', 'password'])
    mockUseForm.mockReturnValue({
      ...mockFailedFormSubmission(errors),
      errors,
    })
    
    render(<Login />)

    expect(screen.getByText('The email field is required.')).toBeInTheDocument()
    expect(screen.getByText('The password field is required.')).toBeInTheDocument()
  })

  it('shows loading state during form submission', () => {
    mockUseForm.mockReturnValue({
      ...mockSuccessfulFormSubmission(),
      processing: true,
    })
    
    render(<Login />)

    const submitButton = screen.getByRole('button', { name: /signing in/i })
    expect(submitButton).toBeDisabled()
    expect(screen.getByText(/signing in/i)).toBeInTheDocument()
  })

  it('displays flash error messages', () => {
    render(<Login />, {
      pageProps: {
        flash: {
          error: 'Invalid credentials provided.',
        },
      },
    })

    expect(screen.getByText('Invalid credentials provided.')).toBeInTheDocument()
  })

  it('handles remember me checkbox', async () => {
    const mockSetData = vi.fn()
    mockUseForm.mockReturnValue({
      ...mockSuccessfulFormSubmission(),
      setData: mockSetData,
      data: { email: '', password: '', remember: false },
    })
    
    const user = userEvent.setup()
    render(<Login />)

    const rememberCheckbox = screen.getByLabelText(/remember me/i)
    await user.click(rememberCheckbox)

    expect(mockSetData).toHaveBeenCalledWith('remember', true)
  })

  it('navigates to register page', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Login />)

    const registerLink = screen.getByRole('link', { name: /sign up/i })
    expect(registerLink).toHaveAttribute('href', '/register')
  })

  it('navigates to forgot password page', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Login />)

    const forgotPasswordLink = screen.getByRole('link', { name: /forgot your password/i })
    expect(forgotPasswordLink).toHaveAttribute('href', '/forgot-password')
  })

  it('handles OAuth redirects', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Login />)

    const googleButton = screen.getByRole('link', { name: /continue with google/i })
    const githubButton = screen.getByRole('link', { name: /continue with github/i })

    expect(googleButton).toHaveAttribute('href', '/auth/google')
    expect(githubButton).toHaveAttribute('href', '/auth/github')
  })

  it('prevents form submission when processing', async () => {
    const mockPost = vi.fn()
    mockUseForm.mockReturnValue({
      ...mockSuccessfulFormSubmission(),
      post: mockPost,
      processing: true,
    })
    
    const user = userEvent.setup()
    render(<Login />)

    const submitButton = screen.getByRole('button')
    await user.click(submitButton)

    expect(mockPost).not.toHaveBeenCalled()
  })

  it('clears password field after failed submission', () => {
    const mockReset = vi.fn()
    mockUseForm.mockReturnValue({
      ...mockFailedFormSubmission({ email: 'Invalid credentials' }),
      reset: mockReset,
    })
    
    render(<Login />)

    expect(mockReset).toHaveBeenCalledWith('password')
  })

  it('has proper accessibility attributes', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Login />)

    const form = screen.getByRole('form')
    expect(form).toBeInTheDocument()

    const emailInput = screen.getByLabelText(/email/i)
    expect(emailInput).toHaveAttribute('type', 'email')
    expect(emailInput).toHaveAttribute('required')

    const passwordInput = screen.getByLabelText(/password/i)
    expect(passwordInput).toHaveAttribute('type', 'password')
    expect(passwordInput).toHaveAttribute('required')
  })

  it('focuses first input on mount', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Login />)

    const emailInput = screen.getByLabelText(/email/i)
    expect(emailInput).toHaveFocus()
  })
})
