/**
 * @jest-environment jsdom
 */
import { render, screen } from '@testing-library/react'
import { TextEncoder, TextDecoder } from 'util'

Object.assign(global, { TextDecoder, TextEncoder })

import { BrowserRouter as Router } from 'react-router-dom'
import LoginPin from '../../../pages/kiosk/Login'
import '@testing-library/jest-dom'

describe('LoginPin', () => {
  it('renders the Epitech location', () => {
    render(
      <Router>
        <LoginPin />
      </Router>
    )
    expect(screen.getByText(/Epitech Paris, France/i)).toBeInTheDocument()
  })

  it('renders the greeting and avatar', () => {
    render(
      <Router>
        <LoginPin />
      </Router>
    )
    expect(screen.getByText(/Hello/i)).toBeInTheDocument()
    const avatar = screen.getByAltText(/User Avatar/i)
    expect(avatar).toBeInTheDocument()
  })

  it('renders the keypad buttons', () => {
    render(
      <Router>
        <LoginPin />
      </Router>
    )
    for (let i = 0; i <= 9; i++) {
      expect(screen.getByText(i.toString())).toBeInTheDocument()
    }
    expect(screen.getByText('✕')).toBeInTheDocument()
  })
})
