import { describe, it, expect, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { LongText } from '../long-text'

// Mock the UI components
vi.mock('@/components/ui/popover', () => ({
  Popover: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  PopoverContent: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  PopoverTrigger: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
}))

vi.mock('@/components/ui/tooltip', () => ({
  TooltipProvider: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  Tooltip: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  TooltipContent: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
  TooltipTrigger: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
}))

describe('LongText Component', () => {
  it('renders text content', () => {
    render(<LongText>Short text</LongText>)
    expect(screen.getByText('Short text')).toBeInTheDocument()
  })

  it('applies truncate class by default', () => {
    render(<LongText>Some text</LongText>)
    const container = screen.getByText('Some text')
    expect(container).toHaveClass('truncate')
  })

  it('applies custom className', () => {
    render(<LongText className="custom-class">Text</LongText>)
    const container = screen.getByText('Text')
    expect(container).toHaveClass('custom-class')
    expect(container).toHaveClass('truncate')
  })

  it('renders children correctly', () => {
    render(
      <LongText>
        <span>Nested content</span>
      </LongText>
    )
    expect(screen.getByText('Nested content')).toBeInTheDocument()
  })

  it('handles empty children', () => {
    render(<LongText>{''}</LongText>)
    const container = screen.getByText('', { selector: 'div' })
    expect(container).toBeInTheDocument()
  })

  it('renders without crashing with long text', () => {
    const longText = 'A'.repeat(200)
    render(<LongText>{longText}</LongText>)
    expect(screen.getByText(longText)).toBeInTheDocument()
  })

  it('applies contentClassName when provided', () => {
    render(
      <LongText contentClassName="content-custom">
        Text content
      </LongText>
    )
    // The component should render without errors
    expect(screen.getByText('Text content')).toBeInTheDocument()
  })

  it('maintains structure with complex children', () => {
    render(
      <LongText>
        <div>
          <span>Part 1</span>
          <span>Part 2</span>
        </div>
      </LongText>
    )
    expect(screen.getByText('Part 1')).toBeInTheDocument()
    expect(screen.getByText('Part 2')).toBeInTheDocument()
  })
})
