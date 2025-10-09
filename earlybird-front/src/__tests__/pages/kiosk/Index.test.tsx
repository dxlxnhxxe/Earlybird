/**
 * @jest-environment jsdom
 */
import { render, screen } from '@testing-library/react'
import '@testing-library/jest-dom'
import IndexPage from '../../../pages/kiosk/Index'

test('renders Epitech message', () => {
    render(<IndexPage />)
    const greetings = screen.getByText(/Epitech/i)
    expect(greetings).toBeInTheDocument()
})
