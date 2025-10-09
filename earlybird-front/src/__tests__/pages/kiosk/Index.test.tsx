/**
 * @jest-environment jsdom
 */
import { render, screen } from '@testing-library/react'
import { TextEncoder, TextDecoder } from 'util'

Object.assign(global, { TextDecoder, TextEncoder })

import { BrowserRouter as Router } from 'react-router-dom'
import HomePage, { users } from '../../../pages/kiosk/Index'
import '@testing-library/jest-dom'

describe('HomePage', () => {
  it('renders the Epitech location', () => {
    render(
      <Router>
        <HomePage />
      </Router>
    )
    expect(screen.getByText(/Epitech Paris, France/i)).toBeInTheDocument()
  })

  it('renders all users in the user list', () => {
    render(
      <Router>
        <HomePage />
      </Router>
    )
    users.forEach(user => {
      expect(screen.getByText(user.name)).toBeInTheDocument()
    })
  })

  it('renders avatars for each user', () => {
    render(
      <Router>
        <HomePage />
      </Router>
    )
    users.forEach(user => {
      const avatar = screen.getByAltText(user.name)
      expect(avatar).toBeInTheDocument()
      expect(avatar).toHaveClass('avatar')
    })
  })
})
