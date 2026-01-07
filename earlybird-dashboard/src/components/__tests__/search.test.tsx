import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { Search } from '../search'

// Mock the search context
const mockSetOpen = vi.fn()
vi.mock('@/context/search-provider', () => ({
  useSearch: () => ({
    setOpen: mockSetOpen,
  }),
}))

describe('Search Component', () => {
  beforeEach(() => {
    mockSetOpen.mockClear()
  })

  it('renders with default placeholder', () => {
    render(<Search />)
    expect(screen.getByText('Search')).toBeInTheDocument()
  })

  it('renders with custom placeholder', () => {
    render(<Search placeholder="Find users..." />)
    expect(screen.getByText('Find users...')).toBeInTheDocument()
  })

  it('displays search icon', () => {
    const { container } = render(<Search />)
    const icon = container.querySelector('svg')
    expect(icon).toBeInTheDocument()
  })

  it('displays keyboard shortcut badge', () => {
    render(<Search />)
    const kbd = screen.getByText('K')
    expect(kbd).toBeInTheDocument()
  })

  it('calls setOpen when clicked', async () => {
    const user = userEvent.setup()
    render(<Search />)
    
    const button = screen.getByRole('button')
    await user.click(button)
    
    expect(mockSetOpen).toHaveBeenCalledWith(true)
    expect(mockSetOpen).toHaveBeenCalledTimes(1)
  })

  it('applies custom className', () => {
    render(<Search className="custom-search" />)
    const button = screen.getByRole('button')
    expect(button).toHaveClass('custom-search')
  })

  it('renders as a button with outline variant', () => {
    render(<Search />)
    const button = screen.getByRole('button')
    expect(button).toHaveClass('bg-muted/25')
  })

  it('has proper accessibility attributes', () => {
    const { container } = render(<Search />)
    const icon = container.querySelector('svg')
    expect(icon).toHaveAttribute('aria-hidden', 'true')
  })

  it('maintains responsive classes', () => {
    render(<Search />)
    const button = screen.getByRole('button')
    expect(button).toHaveClass('sm:w-40')
    expect(button).toHaveClass('lg:w-52')
    expect(button).toHaveClass('xl:w-64')
  })
})
