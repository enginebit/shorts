import { describe, it, expect, vi, beforeEach } from 'vitest'
import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { render, createMockUser, createMockWorkspace, createMockLink } from '../utils/test-utils'
import Dashboard from '@/pages/dashboard'

describe('Dashboard Page', () => {
  const mockUser = createMockUser()
  const mockWorkspace = createMockWorkspace()
  const mockLinks = [
    createMockLink({ id: 'link_1', key: 'abc123', clicks: 150 }),
    createMockLink({ id: 'link_2', key: 'def456', clicks: 89 }),
    createMockLink({ id: 'link_3', key: 'ghi789', clicks: 234 }),
  ]

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders dashboard for authenticated user', () => {
    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    expect(screen.getByText(`Welcome back, ${mockUser.name}`)).toBeInTheDocument()
    expect(screen.getByText(mockWorkspace.name)).toBeInTheDocument()
  })

  it('displays workspace statistics', () => {
    const workspaceWithStats = {
      ...mockWorkspace,
      usage: {
        links: 25,
        clicks: 1250,
      },
    }

    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: workspaceWithStats,
      },
    })

    expect(screen.getByText('25')).toBeInTheDocument() // Links count
    expect(screen.getByText('1,250')).toBeInTheDocument() // Clicks count
  })

  it('displays recent links', () => {
    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    expect(screen.getByText('Recent Links')).toBeInTheDocument()
    
    // Check that links are displayed
    mockLinks.forEach(link => {
      expect(screen.getByText(link.key)).toBeInTheDocument()
      expect(screen.getByText(link.clicks.toString())).toBeInTheDocument()
    })
  })

  it('shows create link button', () => {
    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    const createButton = screen.getByRole('button', { name: /create link/i })
    expect(createButton).toBeInTheDocument()
  })

  it('displays empty state when no links exist', () => {
    render(<Dashboard links={[]} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    expect(screen.getByText(/no links yet/i)).toBeInTheDocument()
    expect(screen.getByText(/create your first link/i)).toBeInTheDocument()
  })

  it('shows analytics overview', () => {
    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    expect(screen.getByText('Analytics Overview')).toBeInTheDocument()
    expect(screen.getByText('Total Clicks')).toBeInTheDocument()
    expect(screen.getByText('Total Links')).toBeInTheDocument()
  })

  it('displays workspace plan information', () => {
    const proWorkspace = {
      ...mockWorkspace,
      plan: 'pro',
    }

    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: proWorkspace,
      },
    })

    expect(screen.getByText(/pro/i)).toBeInTheDocument()
  })

  it('shows upgrade prompt for free plan', () => {
    const freeWorkspace = {
      ...mockWorkspace,
      plan: 'free',
      usage: {
        links: 45, // Close to limit
        clicks: 800,
      },
    }

    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: freeWorkspace,
      },
    })

    expect(screen.getByText(/upgrade/i)).toBeInTheDocument()
  })

  it('handles link click navigation', async () => {
    const user = userEvent.setup()
    
    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    const firstLink = screen.getByText(mockLinks[0].key)
    await user.click(firstLink)

    // Should navigate to link details (mocked)
    expect(firstLink.closest('a')).toHaveAttribute('href', `/links/${mockLinks[0].id}`)
  })

  it('displays user avatar and name', () => {
    const userWithAvatar = {
      ...mockUser,
      image: 'https://example.com/avatar.jpg',
    }

    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: userWithAvatar },
        workspace: mockWorkspace,
      },
    })

    const avatar = screen.getByRole('img', { name: userWithAvatar.name })
    expect(avatar).toHaveAttribute('src', userWithAvatar.image)
  })

  it('shows workspace switcher', () => {
    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    expect(screen.getByText(mockWorkspace.name)).toBeInTheDocument()
    // Should have dropdown or switcher functionality
  })

  it('displays quick actions', () => {
    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    expect(screen.getByText(/create link/i)).toBeInTheDocument()
    expect(screen.getByText(/view analytics/i)).toBeInTheDocument()
  })

  it('shows recent activity', () => {
    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    expect(screen.getByText('Recent Activity')).toBeInTheDocument()
  })

  it('handles workspace plan limits', () => {
    const limitedWorkspace = {
      ...mockWorkspace,
      plan: 'free',
      usage: {
        links: 50, // At limit
        clicks: 1000,
      },
      limits: {
        links: 50,
        clicks: 1000,
      },
    }

    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: limitedWorkspace,
      },
    })

    expect(screen.getByText(/limit reached/i)).toBeInTheDocument()
  })

  it('displays loading state', () => {
    render(<Dashboard links={undefined} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    expect(screen.getByText(/loading/i)).toBeInTheDocument()
  })

  it('shows error state when data fails to load', () => {
    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
        flash: {
          error: 'Failed to load dashboard data.',
        },
      },
    })

    expect(screen.getByText('Failed to load dashboard data.')).toBeInTheDocument()
  })

  it('has proper accessibility attributes', () => {
    render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    // Check for proper headings hierarchy
    expect(screen.getByRole('heading', { level: 1 })).toBeInTheDocument()
    
    // Check for navigation landmarks
    expect(screen.getByRole('main')).toBeInTheDocument()
    
    // Check for proper button labels
    const createButton = screen.getByRole('button', { name: /create link/i })
    expect(createButton).toBeInTheDocument()
  })

  it('updates in real-time when new data is available', () => {
    const { rerender } = render(<Dashboard links={mockLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    // Simulate new link being added
    const updatedLinks = [
      ...mockLinks,
      createMockLink({ id: 'link_4', key: 'new123', clicks: 0 }),
    ]

    rerender(<Dashboard links={updatedLinks} />, {
      pageProps: {
        auth: { user: mockUser },
        workspace: mockWorkspace,
      },
    })

    expect(screen.getByText('new123')).toBeInTheDocument()
  })
})
