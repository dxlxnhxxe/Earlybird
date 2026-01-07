// Test utilities and helpers for integration tests
import React from 'react'
import { render } from '@testing-library/react'

/**
 * Mock user data factory
 */
export const createMockUser = (overrides = {}) => ({
  id: '1',
  firstName: 'John',
  lastName: 'Doe',
  email: 'john.doe@example.com',
  phoneNumber: '+1234567890',
  status: 'active' as const,
  role: 'user' as const,
  createdAt: new Date('2024-01-01'),
  updatedAt: new Date('2024-01-01'),
  ...overrides,
})

/**
 * Create multiple mock users
 */
export const createMockUsers = (count: number = 5) => {
  return Array.from({ length: count }, (_, i) => createMockUser({
    id: String(i + 1),
    firstName: `User${i + 1}`,
    email: `user${i + 1}@example.com`,
  }))
}

/**
 * Mock API response helpers
 */
export const mockApiSuccess = <T,>(data: T, delay = 100) => {
  return new Promise(resolve => 
    setTimeout(() => resolve({ data }), delay)
  )
}

export const mockApiError = (message = 'API Error', status = 500) => {
  return Promise.reject({ 
    response: { 
      data: { message },
      status 
    } 
  })
}

/**
 * Render component with all necessary providers
 * Extend this function as needed for your app's context providers
 */
export const renderWithProviders = (
  ui: React.ReactElement,
  options = {}
) => {
  // Add your app's providers here
  // Example:
  // const Wrapper = ({ children }: { children: React.ReactNode }) => (
  //   <QueryClientProvider client={testQueryClient}>
  //     <BrowserRouter>
  //       {children}
  //     </BrowserRouter>
  //   </QueryClientProvider>
  // )
  
  // For now, just use standard render
  return render(ui, options)
}

/**
 * Utility to wait for async operations
 */
export const sleep = (ms: number) => 
  new Promise(resolve => setTimeout(resolve, ms))
