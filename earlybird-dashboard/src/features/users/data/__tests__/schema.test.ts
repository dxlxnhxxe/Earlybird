import { describe, it, expect } from 'vitest'
import { z } from 'zod'
import { userListSchema, type User, type UserStatus } from '../schema'

describe('User Schema', () => {
  describe('User type', () => {
    it('validates a complete user object', () => {
      const validUser = {
        id: '123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john@example.com',
        phoneNumber: '+1234567890',
        status: 'active' as UserStatus,
        role: 'user' as const,
        createdAt: new Date(),
        updatedAt: new Date(),
      }

      const result = userListSchema.parse([validUser])
      expect(result).toHaveLength(1)
      expect(result[0]).toMatchObject({
        id: '123',
        firstName: 'John',
        lastName: 'Doe',
      })
    })

    it('validates user with optional codePin', () => {
      const userWithPin = {
        id: '123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john@example.com',
        phoneNumber: '+1234567890',
        codePin: 1234,
        status: 'active' as UserStatus,
        role: 'user' as const,
        createdAt: new Date(),
        updatedAt: new Date(),
      }

      expect(() => userListSchema.parse([userWithPin])).not.toThrow()
    })

    it('accepts all valid user statuses', () => {
      const statuses: UserStatus[] = ['active', 'inactive', 'invited', 'suspended']
      
      statuses.forEach(status => {
        const user = {
          id: '123',
          firstName: 'John',
          lastName: 'Doe',
          email: 'john@example.com',
          phoneNumber: '+1234567890',
          status,
          role: 'user' as const,
          createdAt: new Date(),
          updatedAt: new Date(),
        }
        expect(() => userListSchema.parse([user])).not.toThrow()
      })
    })

    it('accepts all valid user roles', () => {
      const roles = ['superadmin', 'admin', 'manager', 'user'] as const
      
      roles.forEach(role => {
        const user = {
          id: '123',
          firstName: 'John',
          lastName: 'Doe',
          email: 'john@example.com',
          phoneNumber: '+1234567890',
          status: 'active' as UserStatus,
          role,
          createdAt: new Date(),
          updatedAt: new Date(),
        }
        expect(() => userListSchema.parse([user])).not.toThrow()
      })
    })

    it('rejects invalid status', () => {
      const invalidUser = {
        id: '123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john@example.com',
        phoneNumber: '+1234567890',
        status: 'invalid_status',
        role: 'user',
        createdAt: new Date(),
        updatedAt: new Date(),
      }

      expect(() => userListSchema.parse([invalidUser])).toThrow()
    })

    it('rejects invalid role', () => {
      const invalidUser = {
        id: '123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john@example.com',
        phoneNumber: '+1234567890',
        status: 'active',
        role: 'invalid_role',
        createdAt: new Date(),
        updatedAt: new Date(),
      }

      expect(() => userListSchema.parse([invalidUser])).toThrow()
    })

    it('requires all mandatory fields', () => {
      const incompleteUser = {
        id: '123',
        firstName: 'John',
        // Missing lastName, email, etc.
      }

      expect(() => userListSchema.parse([incompleteUser])).toThrow()
    })

    it('coerces date strings to Date objects', () => {
      const user = {
        id: '123',
        firstName: 'John',
        lastName: 'Doe',
        email: 'john@example.com',
        phoneNumber: '+1234567890',
        status: 'active' as UserStatus,
        role: 'user' as const,
        createdAt: '2024-01-01T00:00:00Z',
        updatedAt: '2024-01-02T00:00:00Z',
      }

      const result = userListSchema.parse([user])
      expect(result[0].createdAt).toBeInstanceOf(Date)
      expect(result[0].updatedAt).toBeInstanceOf(Date)
    })
  })

  describe('User list schema', () => {
    it('validates an array of users', () => {
      const users = [
        {
          id: '1',
          firstName: 'John',
          lastName: 'Doe',
          email: 'john@example.com',
          phoneNumber: '+1234567890',
          status: 'active' as UserStatus,
          role: 'user' as const,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
        {
          id: '2',
          firstName: 'Jane',
          lastName: 'Smith',
          email: 'jane@example.com',
          phoneNumber: '+0987654321',
          status: 'inactive' as UserStatus,
          role: 'admin' as const,
          createdAt: new Date(),
          updatedAt: new Date(),
        },
      ]

      const result = userListSchema.parse(users)
      expect(result).toHaveLength(2)
      expect(result[0].firstName).toBe('John')
      expect(result[1].firstName).toBe('Jane')
    })

    it('validates empty array', () => {
      const result = userListSchema.parse([])
      expect(result).toEqual([])
    })

    it('rejects non-array input', () => {
      expect(() => userListSchema.parse('not an array')).toThrow()
      expect(() => userListSchema.parse({})).toThrow()
      expect(() => userListSchema.parse(null)).toThrow()
    })
  })
})
