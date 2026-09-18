/**
 * @jest-environment jsdom
 */
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { TextEncoder, TextDecoder } from 'util'

Object.assign(global, { TextDecoder, TextEncoder })

import { BrowserRouter as Router } from 'react-router-dom'
import HomePage from '../../../pages/kiosk/Index'
import '@testing-library/jest-dom'

const mockUsers = [
  { id: 1, firstname: 'Alice', lastname: 'Durand', role: 'user' },
  { id: 2, firstname: 'Bob', lastname: 'Martin', role: 'user' },
]

beforeEach(() => {
  global.fetch = jest.fn().mockResolvedValue({
    ok: true,
    json: async () => mockUsers,
  }) as jest.Mock
})

afterEach(() => {
  jest.restoreAllMocks()
})

describe('HomePage (kiosk user selection)', () => {
  it('renders all users fetched from the API', async () => {
    render(
      <Router>
        <HomePage />
      </Router>
    )

    await waitFor(() => {
      expect(screen.getByText('Alice Durand')).toBeInTheDocument()
      expect(screen.getByText('Bob Martin')).toBeInTheDocument()
    })
  })

  it('renders an avatar for each user', async () => {
    render(
      <Router>
        <HomePage />
      </Router>
    )

    await waitFor(() => {
      const avatar = screen.getByAltText('Alice Durand')
      expect(avatar).toBeInTheDocument()
      expect(avatar).toHaveClass('avatar')
    })
  })

  it('filters the user list as text is typed in the search box', async () => {
    const user = userEvent.setup()
    render(
      <Router>
        <HomePage />
      </Router>
    )

    await waitFor(() => {
      expect(screen.getByText('Alice Durand')).toBeInTheDocument()
    })

    await user.type(screen.getByPlaceholderText('Search'), 'Bob')

    expect(screen.queryByText('Alice Durand')).not.toBeInTheDocument()
    expect(screen.getByText('Bob Martin')).toBeInTheDocument()
  })
})
