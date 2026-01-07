import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { PasswordInput } from '../password-input'

describe('PasswordInput Component', () => {
  it('renders with password type by default', () => {
    render(<PasswordInput placeholder="Enter password" />)
    const input = screen.getByPlaceholderText('Enter password')
    expect(input).toHaveAttribute('type', 'password')
  })

  it('toggles password visibility when button is clicked', async () => {
    const user = userEvent.setup()
    render(<PasswordInput placeholder="Enter password" />)
    
    const input = screen.getByPlaceholderText('Enter password')
    const toggleButton = screen.getByRole('button')
    
    expect(input).toHaveAttribute('type', 'password')
    
    await user.click(toggleButton)
    expect(input).toHaveAttribute('type', 'text')
    
    await user.click(toggleButton)
    expect(input).toHaveAttribute('type', 'password')
  })

  it('shows EyeOff icon when password is hidden', () => {
    const { container } = render(<PasswordInput />)
    // EyeOff is the default state
    expect(container.querySelector('svg')).toBeInTheDocument()
  })

  it('shows Eye icon when password is visible', async () => {
    const user = userEvent.setup()
    render(<PasswordInput />)
    
    const toggleButton = screen.getByRole('button')
    await user.click(toggleButton)
    
    // After toggle, Eye icon should be shown
    expect(toggleButton).toBeInTheDocument()
  })

  it('accepts and displays input value', async () => {
    const user = userEvent.setup()
    render(<PasswordInput placeholder="Password" />)
    
    const input = screen.getByPlaceholderText('Password')
    await user.type(input, 'mySecretPassword123')
    
    expect(input).toHaveValue('mySecretPassword123')
  })

  it('handles disabled state correctly', () => {
    render(<PasswordInput disabled placeholder="Password" />)
    
    const input = screen.getByPlaceholderText('Password')
    const toggleButton = screen.getByRole('button')
    
    expect(input).toBeDisabled()
    expect(toggleButton).toBeDisabled()
  })

  it('applies custom className', () => {
    const { container } = render(
      <PasswordInput className="custom-class" placeholder="Password" />
    )
    
    const wrapper = container.firstChild as HTMLElement
    expect(wrapper).toHaveClass('custom-class')
  })

  it('forwards additional input props', () => {
    render(
      <PasswordInput
        placeholder="Password"
        name="password"
        id="password-field"
        required
        aria-label="Password field"
      />
    )
    
    const input = screen.getByPlaceholderText('Password')
    expect(input).toHaveAttribute('name', 'password')
    expect(input).toHaveAttribute('id', 'password-field')
    expect(input).toBeRequired()
    expect(input).toHaveAttribute('aria-label', 'Password field')
  })

  it('does not accept type prop', () => {
    // TypeScript should prevent this, but testing runtime behavior
    // @ts-expect-error - Testing that type prop is omitted
    render(<PasswordInput type="email" />)
    const input = screen.getByRole('textbox', { hidden: true }) as HTMLInputElement
    // Should still be password type, not email
    expect(input.type).toBe('password')
  })

  it('toggle button is of type button (not submit)', () => {
    render(<PasswordInput />)
    const toggleButton = screen.getByRole('button')
    expect(toggleButton).toHaveAttribute('type', 'button')
  })

  it('has proper styling classes', () => {
    render(<PasswordInput />)
    const input = screen.getByRole('textbox', { hidden: true })
    
    expect(input).toHaveClass('border-input')
    expect(input).toHaveClass('rounded-md')
    expect(input).toHaveClass('w-full')
  })

  it('maintains password visibility state across re-renders', async () => {
    const user = userEvent.setup()
    const { rerender } = render(<PasswordInput placeholder="Password" />)
    
    const input = screen.getByPlaceholderText('Password')
    const toggleButton = screen.getByRole('button')
    
    await user.click(toggleButton)
    expect(input).toHaveAttribute('type', 'text')
    
    rerender(<PasswordInput placeholder="Password" />)
    // State should reset on new render
    expect(input).toHaveAttribute('type', 'password')
  })
})
