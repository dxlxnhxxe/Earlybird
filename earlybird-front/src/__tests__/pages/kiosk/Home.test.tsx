/**
 * @jest-environment jsdom
 */
import { render, screen } from '@testing-library/react'
import { TextEncoder, TextDecoder } from 'util'

Object.assign(global, { TextDecoder, TextEncoder })

import { BrowserRouter as Router } from 'react-router-dom'
import HomePage from '../../../pages/kiosk/Home'
import '@testing-library/jest-dom'

describe('HomePage', () => {
  it('renders "Welcome to EarlyBird Kiosk"', () => {
    render(
      <Router>
        <HomePage />
      </Router>
    )
    expect(screen.getByText(/Welcome to EarlyBird Kiosk/i)).toBeInTheDocument()
  })
})
