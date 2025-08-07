import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render, createMockErrors, mockSuccessfulFormSubmission, mockFailedFormSubmission } from '../../utils/test-utils'
import Register from '@/pages/auth/register'

// Mock the useForm hook
const mockUseForm = vi.fn()
vi.mock('@inertiajs/react', async () => {
  const actual = await vi.importActual('@inertiajs/react')
  return {
    ...actual,
    useForm: () => mockUseForm(),
  }
})

describe('Register Page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders registration form correctly', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Register />)

    // Check for form elements
    expect(screen.getByRole('heading', { name: /create your account/i })).toBeInTheDocument()
    expect(screen.getByLabelText(/name/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/email/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/^password$/i)).toBeInTheDocument()
    expect(screen.getByLabelText(/confirm password/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /create account/i })).toBeInTheDocument()
    
    // Check for links
    expect(screen.getByText(/already have an account/i)).toBeInTheDocument()
  })

  it('displays OAuth registration options', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Register />)

    // Check for OAuth buttons
    expect(screen.getByText(/continue with google/i)).toBeInTheDocument()
    expect(screen.getByText(/continue with github/i)).toBeInTheDocument()
  })

  it('handles form input changes', async () => {
    const mockSetData = vi.fn()
    mockUseForm.mockReturnValue({
      ...mockSuccessfulFormSubmission(),
      setData: mockSetData,
      data: { name: '', email: '', password: '', password_confirmation: '' },
    })
    
    const user = userEvent.setup()
    render(<Register />)

    const nameInput = screen.getByLabelText(/name/i)
    const emailInput = screen.getByLabelText(/email/i)
    const passwordInput = screen.getByLabelText(/^password$/i)
    const confirmPasswordInput = screen.getByLabelText(/confirm password/i)

    await user.type(nameInput, 'John Doe')
    await user.type(emailInput, 'john@example.com')
    await user.type(passwordInput, 'password123')
    await user.type(confirmPasswordInput, 'password123')

    expect(mockSetData).toHaveBeenCalledWith('name', 'John Doe')
    expect(mockSetData).toHaveBeenCalledWith('email', 'john@example.com')
    expect(mockSetData).toHaveBeenCalledWith('password', 'password123')
    expect(mockSetData).toHaveBeenCalledWith('password_confirmation', 'password123')
  })

  it('submits form with correct data', async () => {
    const mockPost = vi.fn()
    mockUseForm.mockReturnValue({
      ...mockSuccessfulFormSubmission(),
      post: mockPost,
      data: { 
        name: 'John Doe',
        email: 'john@example.com', 
        password: 'password123',
        password_confirmation: 'password123'
      },
    })
    
    const user = userEvent.setup()
    render(<Register />)

    const submitButton = screen.getByRole('button', { name: /create account/i })
    await user.click(submitButton)

    expect(mockPost).toHaveBeenCalledWith('/register', {
      onFinish: expect.any(Function),
    })
  })

  it('displays validation errors', () => {
    const errors = createMockErrors(['name', 'email', 'password'])
    mockUseForm.mockReturnValue({
      ...mockFailedFormSubmission(errors),
      errors,
    })
    
    render(<Register />)

    expect(screen.getByText('The name field is required.')).toBeInTheDocument()
    expect(screen.getByText('The email field is required.')).toBeInTheDocument()
    expect(screen.getByText('The password field is required.')).toBeInTheDocument()
  })

  it('shows loading state during form submission', () => {
    mockUseForm.mockReturnValue({
      ...mockSuccessfulFormSubmission(),
      processing: true,
    })
    
    render(<Register />)

    const submitButton = screen.getByRole('button', { name: /creating account/i })
    expect(submitButton).toBeDisabled()
    expect(screen.getByText(/creating account/i)).toBeInTheDocument()
  })

  it('displays flash error messages', () => {
    render(<Register />, {
      pageProps: {
        flash: {
          error: 'Email address is already taken.',
        },
      },
    })

    expect(screen.getByText('Email address is already taken.')).toBeInTheDocument()
  })

  it('validates password confirmation match', async () => {
    const errors = { password_confirmation: 'The password confirmation does not match.' }
    mockUseForm.mockReturnValue({
      ...mockFailedFormSubmission(errors),
      errors,
    })
    
    render(<Register />)

    expect(screen.getByText('The password confirmation does not match.')).toBeInTheDocument()
  })

  it('navigates to login page', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Register />)

    const loginLink = screen.getByRole('link', { name: /sign in/i })
    expect(loginLink).toHaveAttribute('href', '/login')
  })

  it('handles OAuth redirects', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Register />)

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
    render(<Register />)

    const submitButton = screen.getByRole('button')
    await user.click(submitButton)

    expect(mockPost).not.toHaveBeenCalled()
  })

  it('clears password fields after failed submission', () => {
    const mockReset = vi.fn()
    mockUseForm.mockReturnValue({
      ...mockFailedFormSubmission({ email: 'Email already exists' }),
      reset: mockReset,
    })
    
    render(<Register />)

    expect(mockReset).toHaveBeenCalledWith('password', 'password_confirmation')
  })

  it('has proper accessibility attributes', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Register />)

    const form = screen.getByRole('form')
    expect(form).toBeInTheDocument()

    const nameInput = screen.getByLabelText(/name/i)
    expect(nameInput).toHaveAttribute('type', 'text')
    expect(nameInput).toHaveAttribute('required')

    const emailInput = screen.getByLabelText(/email/i)
    expect(emailInput).toHaveAttribute('type', 'email')
    expect(emailInput).toHaveAttribute('required')

    const passwordInput = screen.getByLabelText(/^password$/i)
    expect(passwordInput).toHaveAttribute('type', 'password')
    expect(passwordInput).toHaveAttribute('required')

    const confirmPasswordInput = screen.getByLabelText(/confirm password/i)
    expect(confirmPasswordInput).toHaveAttribute('type', 'password')
    expect(confirmPasswordInput).toHaveAttribute('required')
  })

  it('shows terms of service and privacy policy links', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Register />)

    expect(screen.getByText(/terms of service/i)).toBeInTheDocument()
    expect(screen.getByText(/privacy policy/i)).toBeInTheDocument()
  })

  it('focuses first input on mount', () => {
    mockUseForm.mockReturnValue(mockSuccessfulFormSubmission())
    
    render(<Register />)

    const nameInput = screen.getByLabelText(/name/i)
    expect(nameInput).toHaveFocus()
  })

  it('validates email format', () => {
    const errors = { email: 'The email must be a valid email address.' }
    mockUseForm.mockReturnValue({
      ...mockFailedFormSubmission(errors),
      errors,
    })
    
    render(<Register />)

    expect(screen.getByText('The email must be a valid email address.')).toBeInTheDocument()
  })

  it('validates password strength requirements', () => {
    const errors = { password: 'The password must be at least 8 characters.' }
    mockUseForm.mockReturnValue({
      ...mockFailedFormSubmission(errors),
      errors,
    })
    
    render(<Register />)

    expect(screen.getByText('The password must be at least 8 characters.')).toBeInTheDocument()
  })
})
